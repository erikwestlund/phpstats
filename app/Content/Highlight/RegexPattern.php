<?php

namespace App\Content\Highlight;

use Tempest\Highlight\IsPattern;
use Tempest\Highlight\Pattern;
use Tempest\Highlight\Tokens\TokenTypeEnum;

/** A highlighting pattern given as a regex with a `match` group. */
final readonly class RegexPattern implements Pattern
{
    use IsPattern;

    public function __construct(private string $pattern, private TokenTypeEnum $type) {}

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getTokenType(): TokenTypeEnum
    {
        return $this->type;
    }
}
