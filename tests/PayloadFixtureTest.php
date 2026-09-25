<?php

declare(strict_types=1);

use Gabrielesbaiz\NovaFieldIndicator\NovaFieldIndicator;
use Gabrielesbaiz\NovaFieldIndicator\Tests\Fixtures\Status;

/*
 * Writes the real serialized payload to disk so the Vitest suite can mount the
 * Vue component against PHP's actual output rather than a hand-written mock.
 * This is the only test that crosses the language boundary, and it is the one
 * that would catch the two halves drifting apart.
 */
it('exports real payloads for the JavaScript suite', function () {
    $cases = [];

    $field = NovaFieldIndicator::make('Status', 'status')->enum(Status::class)->withIcons();
    $field->value = 'active';
    $cases['enum'] = $field->jsonSerialize();

    $field = NovaFieldIndicator::make('Status', 'status')->pill()->size('lg')->colors(['a' => 'danger']);
    $field->value = 'a';
    $cases['pill'] = $field->jsonSerialize();

    $field = NovaFieldIndicator::make('Tags', 'tags')->colors(['x' => 'success', 'y' => 'info']);
    $field->value = ['x', 'y'];
    $cases['multi'] = $field->jsonSerialize();

    $field = NovaFieldIndicator::make('Status', 'status')->withoutLabel()->labels(['a' => 'Active']);
    $field->value = 'a';
    $cases['nolabel'] = $field->jsonSerialize();

    $field = NovaFieldIndicator::make('Status', 'status')->hideWhen('a');
    $field->value = 'a';
    $cases['hidden'] = $field->jsonSerialize();

    $keep = ['indicators', 'shape', 'size', 'shouldHide', 'emptyText'];
    $cases = array_map(static fn (array $c): array => array_intersect_key($c, array_flip($keep)), $cases);

    file_put_contents(
        __DIR__.'/js/fixtures/payloads.json',
        json_encode($cases, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n",
    );

    expect($cases['enum']['indicators'][0]['label'])->toBe('Active');
});
