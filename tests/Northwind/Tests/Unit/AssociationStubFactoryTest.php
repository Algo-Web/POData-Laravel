<?php

namespace Tests\Northwind\AlgoWeb\PODataLaravel\Tests\Unit;

use AlgoWeb\PODataLaravel\Models\ObjectMap\Entities\Associations\AssociationStubFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Customer;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Employee;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\InventoryTransaction;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\InventoryTransactionType;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Invoice;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Order;
use Tests\Northwind\AlgoWeb\PODataLaravel\Models\Privilege;
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
     */
    public function testAssociationStubFactory($relationName, $from, $to, $thisField, $thatField, $throughChain)
    {
        $this->assertTrue(class_exists($from), '$from paramater must be a class');
        $this->assertTrue(class_exists($from), '$to paramater must be a class');
        $this->assertInstanceOf(Model::class, new $from(), sprintf('$from Should Be instance of %s', Model::class));
        $this->assertInstanceOf(Model::class, new $to(), sprintf('$to Should Be instance of %s', Model::class));
        $this->assertTrue(method_exists($from, $relationName), sprintf('%s is not a method on %s',$relationName,$from));
        $relationType = get_class((new $from())->{$relationName}());
        $relationXonY = sprintf('Relation: %s  ' . "\r\n" .
            'On: %s ' . "\r\n" .
            'of Type: %s'  . "\r\n" .
            'should ',$relationName, $from, $relationType);
        $this->assertTrue(is_subclass_of($relationType,Relation::class), sprintf($relationXonY . 'be a subclass of: %s', Relation::class));

        $stub =  AssociationStubFactory::associationStubFromRelation(new $from(),$relationName);
        $this->assertEquals($from, $stub->getBaseType(), "the base type of a relationship should be the model on which the relation lives");
        $this->assertEquals($to, $stub->getTargType(), sprintf($relationXonY . 'Target Type should be', $to));
        $this->assertEquals($relationName, $stub->getRelationName(), sprintf($relationXonY . " be named %s", $relationName));
        $this->assertEquals($thisField, $stub->getKeyField(),sprintf($relationXonY . ' have a key field on %s side of the relation', $from));
        $this->assertEquals($thatField, $stub->getForeignField(),sprintf($relationXonY . ' have a Foreign field on %s side of the relation', $to));
        $this->assertEquals($throughChain, $stub->getThroughFieldChain(), sprintf($relationXonY . 'should have through chain[' . implode(', ', $throughChain) . ']'));
    }
    public function associationStubFactoryProvider(){
        // string         string         string  string|null string|null string|null     array
        // $relationName, $from,          $to,   $thisField, $thatField, $throughField, $throughChain
        return [
            ['orders', Customer::class, Order::class, 'id', 'customer_id', ['id','customer_id']], // Has Many
            ['privileges', Employee::class, Privilege::class, 'id', 'id', ['id','employee_id', 'privilege_id', 'id']], //Belongs To Many
            ['inventoryTransactionType', InventoryTransaction::class, InventoryTransactionType::class, 'transaction_type', 'id',['transaction_type', 'id']], // BelongsTo
            ['invoice', Order::class, Invoice::class, 'id', 'order_id',['id', 'order_id']], // Has one
            ['invoices', Customer::class, Invoice::class, 'id', 'order_id', ['id','customer_id','id','order_id']],
            ['customer', Invoice::class, Customer::class, 'order_id', 'id', ['order_id','id','customer_id','id']],
        ];
    }

    /**
     * @dataProvider associationStubFactoryProvider
     * @param $oneModel
     * @param $oneRel
     * @param $twoModel
     * @param $twoRel
     * @param $compatable
     */
    public function testAssociationStubCompatabile($oneModel, $oneRel, $twoModel, $twoRel, $compatable)
    {
        $oneStub = AssociationStubFactory::associationStubFromRelation(new $oneModel(),$oneRel);
        $twoStub = AssociationStubFactory::associationStubFromRelation(new $twoModel(),$twoRel);
        $this->assertequals($compatable, $oneStub->isCompatible($twoStub));
        $this->assertequals($compatable, $twoStub->isCompatible($oneStub));
    }

    public function associationStubCompatabileProvider(){
        return [
            [Customer::class, 'order', Order::class, 'customer',true],
            [Customer::class, 'order', Employee::class, 'privileges',false],
        ];
    }
}
