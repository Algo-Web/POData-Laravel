<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 9/02/20
 * Time: 1:29 PM.
 */
namespace Tests\Regression\AlgoWeb\PODataLaravel\Bgoak\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $cityId
 * @property string $name
 * @property string $postcode
 * @property string $country
 */
class City extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table      = 'test_cities';
    public $timestamps    = false;
    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $primaryKey = 'cityId';
    protected $fillable   = [
        'name',
        'postcode',
        'country'
    ];

    protected $guarded = [];

    public function Address()
    {
        return $this->hasMany(Address::class, 'cityid');
    }
}
