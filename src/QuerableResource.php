<?php
namespace Plokko\QuerableResource;

use Exception;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\Json\Resource;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;

abstract class QuerableResource implements Responsable, JsonSerializable, UrlRoutable, IteratorAggregate, Arrayable
{
    protected
        $useResource            = null,

        /**
         * Set the default items per page or disable pagination if null
         * @var null|int Default items per page or null if disabled
         */
        $paginate               = null,
        /**
         * Allowed client-defined paginations,
         *  - if null no client pagination is allowed
         *  - if int set the maxium page size allowed
         *  - if array only the page sizes listed in the array are allowed
         * @var int|array|null
         */
        $paginations            = 20,

        /**
         * Fields that can be filtered as
         *
         * @var array
         */
        $filteredFields         = [],
        /**
         * Query parameter to use as filter if null the fields will be taken from the root
         * @var string|null
         */
        $filterQueryParameter   = 'filter',



        /**
         * Set the field name used for sorting
         * @var string
         */
        $orderQueryParameter    = 'order_by',
        /**
         * List of orderable fields or null if disabled.
         * Field can be specified as 'field' or 'alias'=>'field'
         * @var null|array
         */
        $orderBy                = null;

    private
        /**
         * @var Resource $resource Resource cache
         */
        $resource;
        
    function __construct(){}

    /**
     * Returns the base query to be used in the resource
     * must be implemented on the final class implementation
     * @return Builder
     */
    abstract protected function getQuery():Builder;

    /**
     * @param Builder $query
     * @param array $filterValues Array of values to filter as:
     *          [
     *              alias => [
     *                  type  - string - type of comparaison to do (ex. '=', 'like', '>=' etc.)
     *                  field - string - field to filter
     *                  value - string - value to filter
     *              ]
     *          ]
     */
    protected function filter(Builder &$query,array $filterValues=[]){

        ///-- Apply filters --//
        foreach($filterValues AS $filter)
        {
            $field = $filter['field'];
            $value = $filter['value'];
            $type  = $filter['type'];

            switch($type)
            {
                case 'equals':$type = '=';
                case '=':case '>=':case '<=':case '<':case '>':
                    $query->where($field,$type,$value);
                    break;
                case 'like':
                    $query->where($field,'like','%'.$value.'%');
                    break;
                case 'startswith':case 'starts_with':
                    $query->where($field,'like',$value.'%');
                    break;
                case 'endswith':case 'ends_with':
                    $query->where($field,'like','%'.$value);
                    break;
                default:
            }
        }
    }

    /**
     * @param Builder $query Query to order
     * @param string|null $field Field to be ordered, null if none is specified (use default ordering)
     * @param string $direction Direction of order (asc or desc)
     */
    protected function orderBy($query,$field=null,$direction='asc'){
        if($field){
            $query->orderBy($field, $direction);
        }
    }

    ///

    /**
     * Return an array of fields to filter
     * @return array
     */
    private final function getFilters(){
        $filters = [];
        foreach($this->filteredFields AS $k=>$v){
            $filterType = '=';

            if(is_numeric($k)){
                $filters[$v]=[
                    'field' => $v,
                    'type'  => '=',
                ];
            }else{
                $filters[$k] = array_merge(['field'=>$k, 'type'=>'=',],(is_array($v)?$v:['field'=>$v]));
            }
        }
        return $filters;
    }

    /**
     * Returns an array of values to be filtered
     *
     *          [
     *              alias => [
     *                  type  - string - type of comparaison to do (ex. '=', 'like', '>=' etc.)
     *                  field - string - field to filter
     *                  value - string - value to filter
     *              ]
     *          ]
     *
     * @return array
     */
    private final function getFilteredValues(\Illuminate\Http\Request $request){
        $queryPrefix = empty($this->filterQueryParameter)?'':$this->filterQueryParameter.'.';
        $filteredValues = [];

        foreach($this->getFilters() AS $alias => $opt){
            if($request->has($queryPrefix.$alias)){
                $value = $request->input($queryPrefix.$alias);
                $filteredValues[$alias] = array_merge($opt,['value' => $value]);
            }
        }

        return $filteredValues;
    }

    /**
     * Build and returns the resource
     * @internal
     * @return \Illuminate\Http\Resources\Json\Resource
     */
    final private function getResource(){
        if(!$this->resource) {
            $query = $this->getQuery();
            $request = request();

            $orderBy = null;

            $this->filter($query, $this->getFilteredValues($request));

            // orderby
            if ($this->orderBy && $request->has($this->orderQueryParameter)) {
                $field = $request->input($this->orderQueryParameter);
                $direction = ($request->input($this->orderQueryParameter . '_dir')) == 'desc' ? 'desc' : 'asc';

                //Orderby alias
                if(array_key_exists($field,$this->orderBy)){
                    $field = $this->orderBy[$field];
                    if(is_array($field)){
                        $direction = !empty($field[1])&&$field[1]==='desc'?'desc':'asc';
                        $field = $field[0];
                    }
                }elseif(!in_array($field, $this->orderBy)){
                    $field = null;
                }


                if ($field){
                    $this->orderBy($query,$field, $direction);
                    $orderBy = compact('field', 'direction');
                }else{
                    $this->orderBy($query);
                }
            }else{
                $this->orderBy($query);
            }

            $result = null;
            if ($this->paginate) {
                $pageSize = $this->paginate;

                if ($this->paginations != null && request()->has('per_page')) {
                    $perPage = intval(request()->input('per_page'));
                    if (is_array($this->paginations)) {
                        if (in_array($perPage, $this->paginations)) {
                            $pageSize = $perPage;
                        }
                    } else
                        $pageSize = min($perPage, $this->paginations);
                }
                $result = $query->paginate($pageSize);
            } else {
                $result = $query->get();
            }

            $resource = call_user_func([$this->useResource ?: \Illuminate\Http\Resources\Json\Resource::class, 'collection'], $result);
            /**@var $resource \Illuminate\Http\Resources\Json\Resource* */

            // Add orderBy info to response
            if ($this->orderBy){
                /*
                $orderBy=[];
                foreach( $query->getQuery()->orders AS $o){
                    $orderBy[]=[
                            'field'     =>$o['column'],
                            'direction' =>$o['direction'],
                        ];
                }
                //*/
                $resource->additional(['orderBy' => $orderBy]);
            }
            $this->resource = $resource;
        }

        return $this->resource;
    }

    /**
     * Sets the pagination
     * @param integer $page_size items per page
     */
    public function paginate($page_size){
        $this->paginate = $page_size;
    }

    /**
     * Set the Http resource class to cast the result into
     * @param string $resourceClassName
     */
    public function useResource($resourceClassName){
        $this->useResource = $resourceClassName;
    }
    
    /**
     * Automatically casts to response
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public final function toResponse($request)
    {
        return $this->getResource()->toResponse($request);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public final function jsonSerialize()
    {
        return $this->getResource();
    }

    public final function __toString()
    {
        return json_encode($this);
    }

    /*** Routing functions ***/
    public final function getRouteKey()
    {
        $this->getQuery()->getRouteKey();
    }

    public final function getRouteKeyName()
    {
        return $this->getQuery()->getRouteKeyName();
    }

    public final function resolveRouteBinding($value)
    {
        throw new Exception('Resources may not be implicitly resolved from route bindings.');
    }

    public final function toResource(){
        return $this->getResource();
    }

    public function getIterator() {
        return $this->getResource();
    }


    function __call($key,$args){
        switch($key){
            // Wrapper for resource methods
            case 'links':
            case 'appends':
                return call_user_func_array([$this->getResource()->resource,$key],$args);
            default:
        }
    }

    public function toArray(){
        return $this->getResource()->toArray(request());
    }
}