# Resource-query
Automatic filtering and sorting for Laravel queries with Ajax capabilities.

## Scope of this package
This package adds classes that help automate the creation of back-end defined user queries (Ajax) with advanced functions like filtering, sorting, pagination, resource casting and smart casting.
The query will be based on a pre-defined Eloqent query and the user may customize the query by applying pre-defined query parameters. 
All the query parameters are easly defined and customized in the back-end allowing strict control on what the user can see or do.

## Installation
Install it via composer
`composer require plokko\resource-query`

## Initialization
To use this class you must extend the base `ResourceQuery` class or use a builder.
Exending `ResourceQuery` class is preferred if you plan to reutilize the same settings, the builder approach is quicker if you plan to use it one time only.

### Extending ResourceQuery
Create a new class that extends `plokko\ResourceQuery\ResourceQuery` and implement the function `getQuery()` that will return the base query

```php
use plokko\ResourceQuery\ResourceQuery;

class ExampleResourceQuery extends ResourceQuery{

    protected function getQuery():Builder {
        // Return the base query
        return MyModel::select('id','a','b')
                ->where('fixed_condition',1);
    }
    
}
```

### Using the builder
Or by defining it in-place with `QueryBuilder`
```php
use plokko\ResourceQuery\QueryBuilder;

$query = MyModel::select('id','etc');
//Add the base query
$resource =  new QueryBuilder($query);
```


## Example usage

```php
class MyController extends Controller {
    //...
    public function example1(Request $request){
        $resource = new ExampleResourceQuery();
        if($request->ajax()){
            return $resource;
        }
        view('example',compact('resource'));
    }
    public function example2(Request $request){
        $query = MyModel::select('id','etc');
        $resource =  new QueryBuilder($query);

        if($request->ajax()){
            return $resource;
        }
        view('example',compact('resource'));
    }
    //...
}
```
