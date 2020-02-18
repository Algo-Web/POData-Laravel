<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 9/02/20
 * Time: 1:28 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $personId
 * @property string $addressid
 * @property string $name
 * @property string $givenname
 */
class Person extends Model
{
    use \AlgoWeb\PODataLaravel\Models\MetadataTrait;
    protected $table = 'test_people';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'personId';
    protected $fillable = [
        'name',
        'givenname',
        'addressid',
        'companyid'
    ];

    protected $guarded = [];

    public function Address()
    {
        return $this->belongsTo(Address::class, 'addressid');
    }
}
