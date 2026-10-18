<?php

namespace App\Content\Highlight;

use Tempest\Highlight\Languages\Base\BaseLanguage;
use Tempest\Highlight\Languages\Php\Patterns\DoubleQuoteValuePattern;
use Tempest\Highlight\Languages\Php\Patterns\SingleQuoteValuePattern;
use Tempest\Highlight\Tokens\TokenTypeEnum;

/** Enough of R for the short comparisons on this site. */
class RLanguage extends BaseLanguage
{
    public function getName(): string
    {
        return 'r';
    }

    public function getPatterns(): array
    {
        return [
            ...parent::getPatterns(),
            new RegexPattern('\b(?<match>function|if|else|for|while|in|return|library|require)\b', TokenTypeEnum::KEYWORD),
            new RegexPattern('(?<match>[A-Za-z_.][A-Za-z0-9_.]*)(?=\s*\()', TokenTypeEnum::PROPERTY),
            new RegexPattern('\b(?<match>TRUE|FALSE|NULL|NA|Inf|NaN)\b', TokenTypeEnum::LITERAL),
            new RegexPattern('(?<![\w.])(?<match>\d+(\.\d+)?(e-?\d+)?L?)', TokenTypeEnum::NUMBER),
            new RegexPattern('(?<match>\|>|%>%|<-|~)', TokenTypeEnum::OPERATOR),
            new SingleQuoteValuePattern,
            new DoubleQuoteValuePattern,
            new RegexPattern('(?<match>#.*)', TokenTypeEnum::COMMENT),
        ];
    }
}
