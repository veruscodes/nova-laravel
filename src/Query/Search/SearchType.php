<?php

namespace Laravel\Nova\Query\Search;

enum SearchType
{
    case EXACT;
    case LIKE;
}
