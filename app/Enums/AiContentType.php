<?php

namespace App\Enums;

enum AiContentType: string
{
    case POST = 'post';
    case CAPTION = 'caption';
    case HASHTAG = 'hashtag';
    case HEADLINE = 'headline';
    case EMAIL = 'email';
    case AD_COPY = 'ad_copy';
    case LANDING_PAGE = 'landing_page';
    case FORM = 'form';
}
