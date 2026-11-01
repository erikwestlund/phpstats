"""Python's results for the landing page's verification section, from the
same public datasets the page's phit code reads. Prints JSON.

    python scripts/verification/references.py > python.json

statsmodels has no maximum-likelihood mixed logistic model (its
BinomialBayesMixedGLM is variational Bayes), so that model has no Python
reference.
"""

import json
import sys

import numpy
import pandas
import scipy
import statsmodels
import statsmodels.formula.api as smf
from scipy import stats

BASE = "https://vincentarelbundock.github.io/Rdatasets/csv/"


def read(path):
    return pandas.read_csv(BASE + path)


def fixed(fit, terms):
    return {
        "terms": terms,
        "coefficients": [float(fit.params[t]) for t in terms],
        "std_errors": [float(fit.bse[t]) for t in terms],
    }


mtcars = read("datasets/mtcars.csv")
ols = smf.ols("mpg ~ wt + hp", data=mtcars).fit()

mroz = read("carData/Mroz.csv")
mroz["lfp"] = (mroz["lfp"] == "yes").astype(int)
formula = "lfp ~ k5 + k618 + age + C(wc, Treatment('no')) + C(hc, Treatment('no')) + lwg + inc"
logit = smf.logit(formula, data=mroz).fit(method="newton", tol=1e-12, maxiter=100, disp=False)
probit = smf.probit(formula, data=mroz).fit(method="newton", tol=1e-12, maxiter=100, disp=False)
glm_terms = ["Intercept", "k5", "k618", "age", "C(wc, Treatment('no'))[T.yes]", "C(hc, Treatment('no'))[T.yes]", "lwg", "inc"]

sleepstudy = read("lme4/sleepstudy.csv")
# MixedLM's default L-BFGS stops early on this flat REML surface (the subject
# variance stays 0.05 from lme4's), so it runs BFGS to a 1e-10 gradient.
mixed = smf.mixedlm("Reaction ~ Days", data=sleepstudy, groups=sleepstudy["Subject"]).fit(reml=True, method=["bfgs"], gtol=1e-10)

out = {
    "versions": {
        "Python": sys.version.split()[0],
        "statsmodels": statsmodels.__version__,
        "scipy": scipy.__version__,
        "numpy": numpy.__version__,
    },
    "settings": {
        "logistic": "Newton, tol 1e-12",
        "probit": "Newton, tol 1e-12",
        "mixed_gaussian": "REML, BFGS, gtol 1e-10",
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
}

print(json.dumps(out, indent=2))
