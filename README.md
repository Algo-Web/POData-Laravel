# POData-Laravel
Composer Package to provide Odata functionality to Laravel
Edit `config/app.php` and add this to providers section:

```php
AlgoWeb\PODataLaravel\Providers\MetadataProvider::class,
AlgoWeb\PODataLaravel\Providers\QueryProvider::class,
```
