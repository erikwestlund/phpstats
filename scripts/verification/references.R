# R's results for the landing page's verification section, from the same
# public datasets the page's phit code reads. Prints JSON.
#
#   Rscript scripts/verification/references.R > r.json
suppressPackageStartupMessages({
  library(lme4)
  library(jsonlite)
})

base <- "https://vincentarelbundock.github.io/Rdatasets/csv/"
read <- function(path) read.csv(paste0(base, path), stringsAsFactors = TRUE)

fixed <- function(fit) {
  s <- summary(fit)$coefficients
  list(terms = rownames(s), coefficients = unname(s[, 1]), std_errors = unname(s[, 2]))
}

mtcars <- read("datasets/mtcars.csv")
ols <- lm(mpg ~ wt + hp, data = mtcars)

mroz <- read("carData/Mroz.csv")
logit <- glm(lfp ~ k5 + k618 + age + wc + hc + lwg + inc, family = binomial(link = "logit"), data = mroz,
  control = glm.control(epsilon = 1e-14, maxit = 100))
probit <- glm(lfp ~ k5 + k618 + age + wc + hc + lwg + inc, family = binomial(link = "probit"), data = mroz,
  control = glm.control(epsilon = 1e-14, maxit = 100))

sleepstudy <- read("lme4/sleepstudy.csv")
gaussian <- lmer(Reaction ~ Days + (1 | Subject), data = sleepstudy, REML = TRUE)

cbpp <- read("lme4/cbpp.csv")
cbpp$period <- factor(cbpp$period)
# glmer's default inner PIRLS tolerance (tolPwrss = 1e-7) stops the
# conditional modes early and perturbs the Laplace log-likelihood by about
# 3e-4 on these data; at 1e-10 it evaluates the Laplace approximation fully.
binomial <- glmer(cbind(incidence, size - incidence) ~ period + (1 | herd), family = binomial, data = cbpp,
  control = glmerControl(tolPwrss = 1e-10))

variance <- function(fit, group) as.numeric(VarCorr(fit)[[group]][1, 1])

out <- list(
  versions = list(R = R.version.string, lme4 = as.character(packageVersion("lme4"))),
  settings = list(
    logistic = "glm, epsilon 1e-14",
    probit = "glm, epsilon 1e-14",
    mixed_gaussian = "lmer, REML, default optimizer",
    mixed_logistic = "glmer, Laplace, tolPwrss 1e-10"
  ),
  distributions = list(
    normal_cdf = pnorm(-8),
    normal_quantile = qnorm(0.975),
    gamma_cdf = pgamma(12, shape = 2, scale = 3),
    gamma_quantile = qgamma(0.999, shape = 2, scale = 3)
  ),
  ols = c(fixed(ols), list(sigma = summary(ols)$sigma, r_squared = summary(ols)$r.squared)),
  logistic = c(fixed(logit), list(log_likelihood = as.numeric(logLik(logit)))),
  probit = c(fixed(probit), list(log_likelihood = as.numeric(logLik(probit)))),
  mixed_gaussian = c(fixed(gaussian), list(
    group_variance = variance(gaussian, "Subject"),
    residual_variance = sigma(gaussian)^2,
    log_likelihood = as.numeric(logLik(gaussian))
  )),
  mixed_logistic = c(fixed(binomial), list(
    group_variance = variance(binomial, "herd"),
    log_likelihood = as.numeric(logLik(binomial))
  ))
)

cat(toJSON(out, digits = NA, auto_unbox = TRUE, pretty = TRUE), "\n")
