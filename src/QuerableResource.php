<?php
namespace Plokko\QuerableResource;

use Exception;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;

abstract class QuerableResource implements Responsable, JsonSerializable, UrlRoutable, IteratorAggregate, Arrayable
{
    protected
        $paginate               = null,
        $filteredFields         = 'filter',
        $useResource            = null,
        $filterQueryParameter   = null,
        /**
         * Allowed client-defined paginations,
         *      if null no client pagination is allowed
         *      if int set the maxium page size allowed
         *      if array only the page sizes listed in the array are allowed
         * @var int|array|null
         */
        $paginations            = 20;

    function __construct(){}


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


    abstract protected function getQuery():Builder;

    ///


    final private function getResource(){
        $query = $this->getQuery();

        $this->filter($query,$this->filterQueryParameter?request()->input($this->filterQueryParameter,[]):request()->all());


        $result = null;
        if($this->paginate){
            $pageSize = $this->paginate;

            if($this->paginations!=null && request()->has('per_page')){
                $perPage = intval(request()->input('per_page'));
                if(is_array($this->paginations)){
                    if(in_array($perPage,$this->paginations)){
                        $pageSize = $perPage;
                    }
                }
                else
                    $pageSize = min($perPage,$this->paginations);
            }
            $result=$query->paginate($pageSize);
        }else{
            $result=$query->get();
        }

        $resource = call_user_func([$this->useResource?:\Illuminate\Http\Resources\Json\Resource::class,'collection'],$result);
        /**@var $resource \Illuminate\Http\Resources\Json\Resource**/

        return $resource;
    }

    public function paginate($page_size){
        $this->paginate = $page_size;
    }

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
            case 'links':
                return $this->getResource()->resource->links();
            default:
        }
    }

    public function toArray(){
        return $this->getResource()->toArray(request());
    }
}