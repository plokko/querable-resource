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
        $paginate = 30,//30 items per page
        /**
         * Allowed client-defined paginations,
         *      if null no client pagination is allowed
         *      if int set the maxium page size allowed
         *      if array only the page sizes listed in the array are allowed
         * @var int|array|null
         */
        $paginations = 100;
      protected function getQuery(): Illuminate\Database\Eloquent\Builder
      {
          return App\User::where('id','>',1); // Just a simple query to demonstrate functionality
      }
}
```
The propriety *$paginations* specify the user-selectable available values, if the value is `null` only *$paginate* user values will be discarted in favor of *$paginate*, if an int value is specified it will be set as the maxium value (in this case the user could select pagination up to 100 with default of 30 items per page); if an array (of integers) is specified the user value must be contained in the array or *$paginate* value will be used (ex. `[10,20,30,50]` )
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

#### Define filtering rules

#### Advanced filtering rules
If your query needs a more fine-tuning you can override the default *filter* function and specify your custom filtering function:
```php
class TestQuerableResource extends \Plokko\QuerableResource\QuerableResource {
	// No need to specify $filteredFields, we're using a custom filtering function

	//Override the default filtering function
    protected function filter(Builder &$query,array $filterValues){
		if(array_key_exists('name',$filterValues)){
			$query->where('name','LIKE','%'.$filterValues['name'].'%');//apply your filtering
		}
		//....
	}

	protected function getQuery(): Illuminate\Database\Eloquent\Builder
	{
		return App\User::where('id','>',1); // Just a simple query to demonstrate functionality
	}
}
```
Don't worry, pagination and query field name will also be automatically applied to your filtering, no need to implement them!

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
The class will now return the specified resource (as a collection);
for further detail on resources see [Laravel 5.5 API resources doc](url=https://laravel.com/docs/5.5/eloquent-resources).

## Javascript integration
This plugin contains some Javascript assets to help integrate with the php counterpart.

This plugin includes two main helper: 
 - *ResourceQuery* For building an Ajax request to the php plugin counterpart 
 - *QuerableResult* Wrapper of *ResourceQuery* that returns a querable result instead of a query, usefull especially in Vue.js applications
 
```javascript
// Import from the /vendor folder, this path is related to /resources/assets/js/components/ 
// Import QuerableResult and ResourceQuery (optional)
import QuerableResult,{ResourceQuery} from "../../../../vendor/plokko/querable-resource/assets/js/QuerableResult";
// Or just ResourceQuery
//import ResourceQuery from "../../../../vendor/plokko/querable-resource/assets/js/ResourceQuery";

```
