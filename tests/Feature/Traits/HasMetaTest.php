<?php

namespace Tests\Feature\Traits;

use App\Models\Resource;
use App\Traits\HasMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class HasMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_use_a_custom_meta_column_name()
    {
        $order = new TestOrder;

        $order->setMeta([
            'path.to.key' => 'value',
        ]);

        $this->assertEquals('value', Arr::get($order->getAttribute('custom_meta'), 'path.to.key'));
    }

    public function test_it_can_set_meta_values()
    {
        $order = new Resource;

        $order->setMeta([
            'path.to.key1' => 'value1',
            'path.to.key2' => 'value2',
        ]);

        $this->assertEquals('value1', Arr::get($order->getAttribute('meta'), 'path.to.key1'));
        $this->assertEquals('value2', Arr::get($order->getAttribute('meta'), 'path.to.key2'));
    }

    public function test_it_can_update_meta_values()
    {
        $order = Resource::factory()->create(); // Assuming you have a factory for Order

        $order->updateMeta([
            'path.to.key1' => 'updatedValue1',
            'path.to.key2' => 'updatedValue2',
        ]);

        $this->assertEquals('updatedValue1', Arr::get($order->getAttribute('meta'), 'path.to.key1'));
        $this->assertEquals('updatedValue2', Arr::get($order->getAttribute('meta'), 'path.to.key2'));
    }

    public function test_it_can_get_meta_values()
    {
        $order = new Resource;

        $order->setMeta([
            'path.to.key1' => 'value1',
            'path.to.key2' => 'value2',
        ]);

        $this->assertEquals('value1', $order->getMeta('path.to.key1'));
        $this->assertEquals('value2', $order->getMeta('path.to.key2'));
        $this->assertNull($order->getMeta('path.to.key3'));
        $this->assertEquals('defaultValue', $order->getMeta('path.to.key3', 'defaultValue'));
    }
}

// A temporary model for testing, using the HasMeta trait
class TestOrder extends Model
{
    use HasMeta;

    // Define the custom meta column name
    protected $metaColumnName = 'custom_meta';

    protected $casts = [
        'custom_meta' => 'array',
    ];
}
