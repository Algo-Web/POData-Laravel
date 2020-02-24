<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 14/02/20
 * Time: 1:52 AM.
 */
namespace Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Unit\Models;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\RelationTestDummyModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use POData\Common\InvalidOperationException;
use Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Models\Address;

class MetadataKeyMethodNamesTraitTest extends TestCase
{
    //use DatabaseMigrations;

    /**
     * @throws InvalidOperationException
     */
    public function testPolyglotKeyMethodNamesForeignKeyNameNotFound()
    {
        $this->markTestSkipped('Sandi can you explain why this test does what it does? ');
        $bar = new Address();

        $rel = $bar->Person();
        $class = get_class($rel);

        $foo = new RelationTestDummyModel();
        $foo->bigReset();
        $foo->setRelationClassMethods([$class => []]);

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('Expected at least 1 element in related-key list, got 0');
    }

    /**
     * @throws InvalidOperationException
     */
    public function testPolyglotKeyMethodNamesRelatedKeyNameNotFound()
    {
        $this->markTestSkipped('Sandi can you explain why this test does what it does? ');

        $bar = new Address();

        $rel = $bar->Person();
        $class = get_class($rel);

        $foo = new RelationTestDummyModel();
        $foo->bigReset();
        $foo->setRelationClassMethods([$class => ['getForeignKey']]);

        $this->expectException(InvalidOperationException::class);
        $this->expectExceptionMessage('Expected at least 1 element in related-key list, got 0');
    }
}
