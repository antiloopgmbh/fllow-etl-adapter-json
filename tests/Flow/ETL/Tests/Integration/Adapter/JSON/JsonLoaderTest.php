<?php

declare(strict_types=1);

namespace Flow\ETL\Tests\Integration\Adapter\JSON;

use Flow\ETL\Adapter\JSON\JsonLoader;
use Flow\ETL\DSL\Json;
use Flow\ETL\Flow;
use Flow\ETL\Row;
use Flow\ETL\Rows;
use Flow\ETL\Stream\LocalFile;
use PHPUnit\Framework\TestCase;

final class JsonLoaderTest extends TestCase
{
    public function test_json_loader() : void
    {
        $stream = new LocalFile(\sys_get_temp_dir() . '/' . \uniqid('flow_php_etl_csv_loader', true) . '.json');

        (new Flow())
            ->process(
                new Rows(
                    ...\array_map(
                        fn (int $i) : Row => Row::create(
                            new Row\Entry\IntegerEntry('id', $i),
                            new Row\Entry\StringEntry('name', 'name_' . $i)
                        ),
                        \range(0, 10)
                    )
                )
            )
            ->write(Json::to($stream))
            ->run();

        $this->assertJsonStringEqualsJsonString(
            <<<'JSON'
[
  {"id":0,"name":"name_0"},
  {"id":1,"name":"name_1"},
  {"id":2,"name":"name_2"},
  {"id":3,"name":"name_3"},
  {"id":4,"name":"name_4"},
  {"id":5,"name":"name_5"},
  {"id":6,"name":"name_6"},
  {"id":7,"name":"name_7"},
  {"id":8,"name":"name_8"},
  {"id":9,"name":"name_9"},
  {"id":10,"name":"name_10"}
]
JSON,
            \file_get_contents($stream->uri())
        );

        if (\file_exists($stream->uri())) {
            \unlink($stream->uri());
        }
    }

    public function test_json_loader_loading_empty_string() : void
    {
        $stream = new LocalFile(\sys_get_temp_dir() . '/' . \uniqid('flow_php_etl_csv_loader', true) . '.json');

        $loader = new JsonLoader($stream);

        $loader->load(new Rows());

        $loader->closure(new Rows());

        $this->assertJsonStringEqualsJsonString(
            <<<'JSON'
[
]
JSON,
            \file_get_contents($stream->uri())
        );

        if (\file_exists($stream->uri())) {
            \unlink($stream->uri());
        }
    }

    public function test_json_loader_with_a_safe_mode() : void
    {
        $stream = \sys_get_temp_dir() . '/' . \uniqid('flow_php_etl_csv_loader', true) . '.json';

        $loader = new JsonLoader($stream, safeMode: true);

        $loader->load(
            new Rows(
                ...\array_map(
                    fn (int $i) : Row => Row::create(
                        new Row\Entry\IntegerEntry('id', $i),
                        new Row\Entry\StringEntry('name', 'name_' . $i)
                    ),
                    \range(0, 5)
                )
            )
        );

        $loader->load(
            new Rows(
                ...\array_map(
                    fn (int $i) : Row => Row::create(
                        new Row\Entry\IntegerEntry('id', $i),
                        new Row\Entry\StringEntry('name', 'name_' . $i)
                    ),
                    \range(6, 10)
                )
            )
        );

        $files = \array_values(\array_diff(\scandir($stream), ['..', '.']));

        $loader->closure(new Rows());

        $this->assertJsonStringEqualsJsonString(
            <<<'JSON'
[
      {"id":0,"name":"name_0"},
      {"id":1,"name":"name_1"},
      {"id":2,"name":"name_2"},
      {"id":3,"name":"name_3"},
      {"id":4,"name":"name_4"},
      {"id":5,"name":"name_5"},
      {"id":6,"name":"name_6"},
      {"id":7,"name":"name_7"},
      {"id":8,"name":"name_8"},
      {"id":9,"name":"name_9"},
      {"id":10,"name":"name_10"}
]
JSON,
            \file_get_contents($stream . DIRECTORY_SEPARATOR . $files[0])
        );

        if (\file_exists($stream . DIRECTORY_SEPARATOR . $files[0])) {
            \unlink($stream . DIRECTORY_SEPARATOR . $files[0]);
        }
    }
}
