"""Python's results for the landing page's verification section, from the
same public datasets the page's phit code reads. Prints JSON.

    python scripts/verification/references.py > python.json

statsmodels has no maximum-likelihood mixed logistic model (its
BinomialBayesMixedGLM is variational Bayes), so the mixed logistic model uses
GPBoost, which fits it by maximum likelihood with the Laplace approximation,
as lme4 does. The packages are in requirements.txt.
"""

import contextlib
import json
import math
import os
import sys

import gpboost
import numpy
import pandas
import scipy
import statsmodels
import statsmodels.formula.api as smf
from scipy import stats

BASE = "https://raw.githubusercontent.com/phpstats/datasets/main/csv/"


def read(path):
    return pandas.read_csv(BASE + path)


def fixed(fit, terms):
    return {
        "terms": terms,
        "coefficients": [float(fit.params[t]) for t in terms],
        "std_errors": [float(fit.bse[t]) for t in terms],
    }


mtcars = read("mtcars.csv")
ols = smf.ols("mpg ~ wt + hp", data=mtcars).fit()

mroz = read("mroz.csv")
mroz["lfp"] = (mroz["lfp"] == "yes").astype(int)
formula = "lfp ~ k5 + k618 + age + C(wc, Treatment('no')) + C(hc, Treatment('no')) + lwg + inc"
logit = smf.logit(formula, data=mroz).fit(method="newton", tol=1e-12, maxiter=100, disp=False)
probit = smf.probit(formula, data=mroz).fit(method="newton", tol=1e-12, maxiter=100, disp=False)
glm_terms = ["Intercept", "k5", "k618", "age", "C(wc, Treatment('no'))[T.yes]", "C(hc, Treatment('no'))[T.yes]", "lwg", "inc"]

sleepstudy = read("sleepstudy.csv")
# MixedLM's default L-BFGS stops early on this flat REML surface (the subject
# variance stays 0.05 from lme4's), so it runs BFGS to a 1e-10 gradient.
mixed = smf.mixedlm("Reaction ~ Days", data=sleepstudy, groups=sleepstudy["Subject"]).fit(reml=True, method=["bfgs"], gtol=1e-10)

# GPBoost takes one 0/1 row per animal. Its log-likelihood differs from the
# grouped binomial one, cbind(incidence, size - incidence), by the constant
# sum of log binomial coefficients, which is added back.
cbpp = read("cbpp.csv")
animals = pandas.DataFrame(
    [
        (1 if i < row["incidence"] else 0, str(row["period"]), row["herd"])
        for _, row in cbpp.iterrows()
        for i in range(int(row["size"]))
    ],
    columns=["y", "period", "herd"],
)
design = pandas.get_dummies(animals["period"], prefix="period", drop_first=True).astype(float)
design.insert(0, "Intercept", 1.0)
@contextlib.contextmanager
def quiet():
    """GPBoost's native code logs to standard output; send it to standard error."""
    sys.stdout.flush()
    saved = os.dup(1)
    os.dup2(2, 1)
    try:
        yield
    finally:
        sys.stdout.flush()
        os.dup2(saved, 1)
        os.close(saved)


with quiet():
    glmm = gpboost.GPModel(group_data=animals["herd"], likelihood="bernoulli_logit")
    glmm.set_optim_params(params={"optimizer_cov": "lbfgs", "delta_rel_conv": 1e-12, "maxit": 10000})
    glmm.fit(y=animals["y"], X=design)
binomial_constant = sum(math.log(math.comb(int(r["size"]), int(r["incidence"]))) for _, r in cbpp.iterrows())

out = {
    "versions": {
        "Python": sys.version.split()[0],
        "statsmodels": statsmodels.__version__,
        "scipy": scipy.__version__,
        "numpy": numpy.__version__,
        "gpboost": gpboost.__version__,
    },
    "settings": {
        "logistic": "Newton, tol 1e-12",
        "probit": "Newton, tol 1e-12",
        "mixed_gaussian": "REML, BFGS, gtol 1e-10",
        "mixed_logistic": "GPBoost, Laplace, L-BFGS, delta_rel_conv 1e-12",
    },
    "distributions": {
        "normal_cdf": float(stats.norm.cdf(-8)),
        "normal_quantile": float(stats.norm.ppf(0.975)),
        "gamma_cdf": float(stats.gamma.cdf(12, a=2, scale=3)),
        "gamma_quantile": float(stats.gamma.ppf(0.999, a=2, scale=3)),
    },
    "ols": {
        **fixed(ols, ["Intercept", "wt", "hp"]),
        "sigma": float(numpy.sqrt(ols.scale)),
        "r_squared": float(ols.rsquared),
    },
    "logistic": {**fixed(logit, glm_terms), "log_likelihood": float(logit.llf)},
    "probit": {**fixed(probit, glm_terms), "log_likelihood": float(probit.llf)},
    "mixed_gaussian": {
        **fixed(mixed, ["Intercept", "Days"]),
        "group_variance": float(mixed.cov_re.iloc[0, 0]),
        "residual_variance": float(mixed.scale),
        "log_likelihood": float(mixed.llf),
    },
    "mixed_logistic": {
        "terms": list(design.columns),
        "coefficients": [float(c) for c in glmm.get_coef().values.ravel()],
        "std_errors": None,
        "group_variance": float(glmm.get_cov_pars().values.ravel()[0]),
        "log_likelihood": float(-glmm.get_current_neg_log_likelihood() + binomial_constant),
    },
}

print(json.dumps(out, indent=2))
