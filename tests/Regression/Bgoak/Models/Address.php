<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 9/02/20
 * Time: 1:29 PM.
 */
namespace Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $addressId
 * @property string $cityid
 * @property string $street
 */
class Address extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table      = 'test_addresses';
    public $timestamps    = false;
    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $primaryKey = 'addressId';
    protected $fillable   = [
        'street',
        'cityid'
    ];

    protected $guarded = [];

    public function Person()
    {
        return $this->hasMany(Person::class, 'addressid');
    }

    public function City()
    {
        return $this->belongsTo(City::class, 'cityid');
    }
}
