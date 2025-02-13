<?php

namespace App\Nova\Filters;

use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;
use App\Models\SentimentData;

class SentimentFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * The displayable name of the filter.
     *
     * @var string
     */
    public $name = 'Sentiment Filter';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(NovaRequest $request, $query, $value)
    {
        $parts = explode(':', $value);
        $category = $parts[0];
        $value = $parts[1];

        return match($category) {
            'source' => $query->where('source_type', $value),
            'sentiment' => $query->where('sentiment_label', $value),
            'confidence' => $this->applyConfidenceFilter($query, $value),
            'impact' => $this->applyImpactFilter($query, $value),
            default => $query,
        };
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request)
    {
        return [
            'Source Type' => [
                'News' => 'source:' . SentimentData::SOURCE_NEWS,
                'Twitter' => 'source:' . SentimentData::SOURCE_TWITTER,
                'Reddit' => 'source:' . SentimentData::SOURCE_REDDIT,
                'Telegram' => 'source:' . SentimentData::SOURCE_TELEGRAM,
                'Other' => 'source:' . SentimentData::SOURCE_OTHER,
            ],
            'Sentiment' => [
                'Very Positive' => 'sentiment:' . SentimentData::SENTIMENT_VERY_POSITIVE,
                'Positive' => 'sentiment:' . SentimentData::SENTIMENT_POSITIVE,
                'Neutral' => 'sentiment:' . SentimentData::SENTIMENT_NEUTRAL,
                'Negative' => 'sentiment:' . SentimentData::SENTIMENT_NEGATIVE,
                'Very Negative' => 'sentiment:' . SentimentData::SENTIMENT_VERY_NEGATIVE,
            ],
            'Confidence Level' => [
                'Very High (90%+)' => 'confidence:very_high',
                'High (80%+)' => 'confidence:high',
                'Medium (60%+)' => 'confidence:medium',
                'Low (<60%)' => 'confidence:low',
            ],
            'Impact Level' => [
                'High Impact (80%+)' => 'impact:high',
                'Medium Impact (50%+)' => 'impact:medium',
                'Low Impact (<50%)' => 'impact:low',
            ],
        ];
    }

    /**
     * Apply confidence-based filters.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyConfidenceFilter($query, $value)
    {
        return match($value) {
            'very_high' => $query->where('confidence_score', '>=', 0.9),
            'high' => $query->where('confidence_score', '>=', 0.8),
            'medium' => $query->where('confidence_score', '>=', 0.6),
            'low' => $query->where('confidence_score', '<', 0.6),
            default => $query,
        };
    }

    /**
     * Apply impact-based filters.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyImpactFilter($query, $value)
    {
        return match($value) {
            'high' => $query->where('impact_score', '>=', 0.8),
            'medium' => $query->where('impact_score', '>=', 0.5),
            'low' => $query->where('impact_score', '<', 0.5),
            default => $query,
        };
    }

    /**
     * The default value of the filter.
     *
     * @return string|null
     */
    public function default()
    {
        return null;
    }
}
