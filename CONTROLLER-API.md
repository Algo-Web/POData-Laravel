#POData-Laravel controller API/howto

0.  Make sure the model(s) your controller deals with use the MetadataTrait.
1.  Add MetadataControllerTrait to your controller.
2.  Set the protected $mapping field as follows:
     An associative array, keyed by model name, and value being the associative array that is mapping for that controller.
     The verbs that you'll have to worry about are: *create*, *update*, *delete*, *bulkCreate* and *bulkUpdate*.
     For example, in your controller's constructor:
```php
             $this->mapping = [
                 TargModel::class =>
                     [
                         'create' => 'store',
                         'update' => 'update',
                         'delete' => 'destroy'
                     ],
                 TargModelSubclass::class =>
                     [
                         'delete' => 'destroy'
                     ]
             ];
```

    The above snippet registers mappings for the TargModel class, for the *create*, *update* and *delete* methods.
    The *bulkCreate* and *bulkUpdate* are not set and thus default to wrappers around their non-bulk versions.
    It also registers the *delete* verb for the TargModelSubclass class.
    You can register as many model-verb mappings as you see fit, but they each have to be globally unique.

3.  Tweak your exposed controller methods to return a JSON payload with three fields: status, id, and errors.

    For instance:
```php
    return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
```
or
```php
    return response()->json(['status' => 'success', 'id' => [1,2,3,4], 'errors' => null]);
```

    Underlying DB operation failed:
    * status is 'error'
    * id is null
    * errors is a list of problems that happened - eg validation errors (MessageBags work well for this)

    Underlying DB operation succeeded:
    * status is 'success'
    * id is either a single primary key value (for non bulk create, update or delete), or an array thereof (for bulk create/update)
    * errors is null