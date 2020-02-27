<?php

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Tests\Unit;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Customer;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Employee;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\InventoryTransaction;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\InventoryTransactionType;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Invoice;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Order;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Photo;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Privilege;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Tag;
use Tests\Northwind\AlgoWeb\PODataLaravel\TestCase;

class AssociationStubFactoryTest extends TestCase
{
    /**
     * @dataProvider associationStubFactoryProvider
     * @param $relationName
     * @param $from
     * @param $to
     * @param $thisField
     * @param $thatField
     * @param $throughField
     * @param $throughChain
     * @throws InvalidOperationException
     */
    public function testAssociationStubFactory($relationName, $from, $to, $thisField, $thatField, $throughChain)
    {
        $this->assertTrue(class_exists($from), '$from parameter must be a class');
        $this->assertTrue(class_exists($from), '$to parameter must be a class');
        $this->assertInstanceOf(Model::class, new $from(), sprintf('$from should Be instance of %s', Model::class));
        (is_null($to)) ?: $this->assertInstanceOf(Model::class, new $to(), sprintf('$to should Be instance of %s', Model::class));
        $this->assertTrue(
            method_exists($from, $relationName),
            sprintf('%s is not a method on %s', $relationName, $from)
        );
        $relationType = get_class((new $from())->{$relationName}());
        $relationXonY = sprintf('Relation: %s  ' . "\r\n" .
            'On: %s ' . "\r\n" .
            'of type: %s'  . "\r\n" .
            'should ', $relationName, $from, $relationType);
        $this->assertTrue(
            is_subclass_of($relationType, Relation::class),
            sprintf($relationXonY . 'be a subclass of: %s', Relation::class)
        );

        $stub = AssociationStubFactory::associationStubFromRelation(new $from(), $relationName);
        $this->assertEquals(
            $from,
            $stub->getBaseType(),
            "the base type of a relationship should be the model on which the relation lives"
        );
        $this->assertEquals($to, $stub->getTargType(), sprintf($relationXonY . ' have a Target Type of %s', $to));
        $this->assertEquals($relationName, $stub->getRelationName(), sprintf($relationXonY . " be named %s", $relationName));
        $this->assertEquals($thisField, $stub->getKeyField(), sprintf($relationXonY . ' have a key field on %s side of the relation', $from));
        $this->assertEquals($thatField, $stub->getForeignField(), sprintf($relationXonY . ' have a foreign field on %s side of the relation', $to));
        $this->assertEquals($throughChain, $stub->getThroughFieldChain(), sprintf($relationXonY . 'should have through chain[' . implode(', ', $throughChain) . ']'));
    }

    public function associationStubFactoryProvider()
    {
        // string         string         string  string|null string|null string|null     array
        // $relationName, $from,          $to,   $thisField, $thatField, $throughField, $throughChain
        return [
            ['orders', Customer::class, Order::class, 'id', 'customer_id', ['id','customer_id']], // Has Many
            ['privileges', Employee::class, Privilege::class, 'id', 'id', ['id','employee_id', 'privilege_id', 'id']], //Belongs To Many
            ['inventoryTransactionType', InventoryTransaction::class, InventoryTransactionType::class, 'transaction_type', 'id',['transaction_type', 'id']], // BelongsTo
            ['invoice', Order::class, Invoice::class, 'id', 'order_id',['id', 'order_id']], // Has one
            ['invoices', Customer::class, Invoice::class, 'id', 'order_id', ['id','customer_id','id','order_id']],
            ['customer', Invoice::class, Customer::class, 'order_id', 'id', ['order_id','id','customer_id','id']],
            //TODO: the morphOneHandler Should do this... but doesnt ['photos', Customer::class, Photo::class, 'id', 'rel_id', ['id', 'rel_type','rel_id']], // Morph One
            //TODO: the morphManyHandler should do this... but doesnt ['photos', Employee::class, Photo::class, 'id', 'rel_id', ['id', 'rel_type','rel_id']], // Morph Many
            //TODO: the morphToHandler should do this... but doesnt ['photoOf', Photo::class,null, 'rel_id', null, ['rel_id', 'rel_type', null]]
            //TODO: the morphToManyHandler should do this... but doesnt ['tags', Customer::class, Tag::class, 'id', 'id', ['id','taggable_id','taggable_type','tag_id', 'id']],
            //TODO: the morphedByManyHandler should do this... but doesnt['taggedCustomer', Tag::class, Customer::class, 'id', 'id', ['id','tag_id','taggable_type','taggable_id', 'id']],
            //TODO: the morphedByManyHandler should do this... but doesnt['taggedEmployees', Tag::class, Employee::class, 'id', 'id', ['id','tag_id','taggable_type','taggable_id', 'id']],
        ];
    }

    /**
     * @dataProvider associationStubCompatibleProvider
     * @param $oneModel
     * @param $oneRel
     * @param $twoModel
     * @param $twoRel
     * @param $compatible
     * @throws InvalidOperationException
     */
    public function testAssociationStubCompatible($oneModel, $oneRel, $twoModel, $twoRel, $compatible)
    {
        $oneRelType = get_class((new $oneModel())->{$oneRel}());
        $twoRelType = get_class((new $twoModel())->{$twoRel}());
        $message = 'Relation: %s  ' . "\r\n" .
            'On: %s ' . "\r\n" .
            'of Type: %s' . "\r\n";
        $oneMessage = sprintf($message, $oneRel, $oneModel, $oneRelType);
        $twoMessage = sprintf($message, $twoRel, $twoModel, $twoRelType);
        $message = sprintf($oneMessage . '%s' . "\r\n" . $twoMessage, $compatible ? 'SHOULD' : 'SHOULD NOT');
        $oneStub = AssociationStubFactory::associationStubFromRelation(new $oneModel(), $oneRel);
        $twoStub = AssociationStubFactory::associationStubFromRelation(new $twoModel(), $twoRel);
        $this->assertEquals($compatible, $oneStub->isCompatible($twoStub), $message);
        $this->assertEquals(
            $compatible,
            $twoStub->isCompatible($oneStub),
            $message . 'especially given the opposite is true'
        );
    }

    public function associationStubCompatibleProvider()
    {
        return [
            [Customer::class, 'orders', Order::class, 'customer',true], //  HasMany -> BelongsTo
            [Customer::class, 'photos', Photo::class, 'photoOf',true], // MorphOne -> MorphTo
            [Employee::class, 'photos', Photo::class, 'photoOf',true], // MorphMany -> MorphTo
            [Employee::class, 'tags', Tag::class, 'taggedEmployees',true], // MorphToMany -> MorphedByMany
            [Customer::class, 'tags', Tag::class, 'taggedCustomer',true], // MorphToMany -> MorphedByMany
            [Order::class, 'invoice', Invoice::class, 'order',true], // HasOne -> BelongsTo
            [Customer::class, 'invoices', Invoice::class, 'customer',true], // HasManyThrough -> HasManyThrough
            [Customer::class, 'orders', Employee::class, 'privileges',false],
            [Customer::class, 'photos', Order::class, 'customer',false],
            // TODO: this tests should pass, but current stub factoy is broke see: testAssociationStubFactory [Employee::class, 'tags', Tag::class, 'taggedCustomer',false],
            // TODO: this tests should pass, but current stub factoy is broke see: testAssociationStubFactory[Customer::class, 'tags', Tag::class, 'taggedEmployees',false],
        ];
    }
}
