# QuerableResource
A Laravel class that helps querying(paging and filtering) and returning Eloquent resources from Ajax.

Supports Laravel 5.5 Resources.
## Installation
Install it throught composer `composer require plokko\querable-resource`

## Quick start
First of all you need to extend the `Plokko\QuerableResource\QuerableResource` class implementing the *getQuery* method that returns the base query:
```php
 class TestQuerableResource extends \Plokko\QuerableResource\QuerableResource {
 
      protected function getQuery(): Illuminate\Database\Eloquent\Builder
      {
          return App\User::where('id','>',1); // Just a simple query to demonstrate functionality
      }
  }

```
Next we're going to instantiate it and return it to the request
```php
Route::get('/test',function(){
    $qr = new TestQuerableResource();
    return $qr;
});
```
The page should return a basic resource with all the results

### Pagination
To enable pagination edit the *$paginate* protected propriety or via the *pagination()* method setting it to the wanted page size or to *null* to disable it.

```php
class TestQuerableResource extends \Plokko\QuerableResource\QuerableResource {
      protected
        $paginate = 30;//30 items per page
      protected function getQuery(): Illuminate\Database\Eloquent\Builder
      {
          return App\User::where('id','>',1); // Just a simple query to demonstrate functionality
      }
}
```
```php
Route::get('/test',function(){
    $qr = new TestQuerableResource();
    $qr->paginate(10); // Set pagination to 10 items per page
    return $qr;
});
```

### Filtering results
To filter results extend the protected propriety *$filteredFields* specifying what fields you want to filter

```php
class TestQuerableResource extends \Plokko\QuerableResource\QuerableResource {
      protected
        $filteredFields = ['name','email']; // enable filtering for name and email columns
      protected function getQuery(): Illuminate\Database\Eloquent\Builder
      {
          return App\User::where('id','>',1); // Just a simple query to demonstrate functionality
      }
}
```
You do not need to pass anything to the resource, the filtering is automatic and transparent.
```php
Route::get('/test',function(){
    $qr = new TestQuerableResource();
    return $qr;
});
```
By default the comparison is done with the *LIKE 'value%'*; for example the page `/test?name=a` will search all the user with a name starting with 'a'.
#### Changing query field name
If you want to group all filtering field in an input group, for example filter[filed_name], you need to edit the protected propriety *$filterQueryParameter* 

```php
class TestQuerableResource extends \Plokko\QuerableResource\QuerableResource {
      protected
        $filteredFields = ['name','email'], // enable filtering for name and email columns
        $filterQueryParameter='filter';
      protected function getQuery(): Illuminate\Database\Eloquent\Builder
      {
          return App\User::where('id','>',1); // Just a simple query to demonstrate functionality
      }
}
```
The url of the last example will now be `/test?filter[name]=a`

### Advanced filtering rules
### Returning custom resource
If you want to filter the results with a custom resource you can do it by simply specifying the name of your Resource class in the protected field *$useResource*:
```php
class TestQuerableResource extends \Plokko\QuerableResource\QuerableResource {
  protected
   $useResource = \App\Http\Resources\UserResource::class;
  protected function getQuery(): Illuminate\Database\Eloquent\Builder
  {
	  return App\User::where('id','>',1); // Just a simple query to demonstrate functionality
  }
}

```

