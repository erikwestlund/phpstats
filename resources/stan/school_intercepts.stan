// score ~ study_hours + (1 | school), with a non-centered random intercept.
// Data come from CmdStan\StanData::fromFormula(); X includes the intercept column.
data {
  int<lower=0> N;
  int<lower=1> K;
  matrix[N, K] X;
  vector[N] y;
  int<lower=1> J;
  array[N] int<lower=1, upper=J> school_id;
}
parameters {
  vector[K] beta;
  real<lower=0> sigma;
  real<lower=0> tau;
  vector[J] z;
}
transformed parameters {
  vector[J] u = tau * z;
}
model {
  beta[1] ~ normal(50, 25);
  beta[2:K] ~ normal(0, 10);
  sigma ~ student_t(3, 0, 10);
  tau ~ student_t(3, 0, 10);
  z ~ std_normal();
  y ~ normal(X * beta + u[school_id], sigma);
}
generated quantities {
  vector[J] school_intercept = beta[1] + u;
}
