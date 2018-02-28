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
         * Fields that can be filtered
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
         * List of orderable fields or null if disabled
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
     * @param Builder $query
     * @param array $filterValues
     */
    protected function filter(Builder &$query,array $filterValues=[]){
        if($this->filteredFields)
        {

            //-- Prepare filters if sequentials --//
            $filters = $this->filteredFields;

            $keys = array_keys($filters);
            if($keys==array_keys($keys)){
                //filter is NOT associative, fill keys
                $filters=array_fill_keys(array_values($filters),'like');
            }
            unset($keys);

            ///-- Apply filters --//
            foreach($filterValues AS $key => $value)
            {
                if(!array_key_exists($key,$filters))
                    continue;
                $filter = $filters[$key];
                $filterType='=';
                if(is_array($filter))
                {
                    $filterType=$filter['type']?:'=';
                }else{
                    $filterType = $filter;
                }
                switch($filterType)
                {
                    case '=':case 'equals':
                        $query->where($key,$value);
                        break;
                    case 'like':
                        $query->where($key,'LIKE',$value.'%');
                    break;
                    default:
                }
            }
        }
    }

    /**
     * Returns the base query to be used in the resource
     * must be implemented on the final class implementation
     * @return Builder
     */
    abstract protected function getQuery():Builder;

    ///

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

            $this->filter($query, $this->filterQueryParameter ? $request->input($this->filterQueryParameter, []) : $request->all());

            // orderby
            if ($this->orderBy && $request->has($this->orderQueryParameter)) {
                $field = $request->input($this->orderQueryParameter);
                $direction = ($request->input($this->orderQueryParameter . '_dir')) == 'desc' ? 'desc' : 'asc';

                if ($field && in_array($field, $this->orderBy)) {
                    $query->orderBy($field, $direction);
                    $orderBy = compact('field', 'direction');
                }
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
            if ($this->orderBy)
                $resource->additional(['orderBy' => $orderBy]);
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