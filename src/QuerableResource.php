<?php
namespace Plokko\QuerableResource;

use Exception;
use Illuminate\Contracts\Routing\UrlRoutable;
use JsonSerializable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;

abstract class QuerableResource implements Responsable, JsonSerializable,UrlRoutable
{
    protected
        $paginate           = null,
        $filteredFields     = null,
        $useResource        = null;


    function __construct(){}


    protected function filter(Builder $query,array $filterValues){
        if($this->filteredFields){
            foreach($filterValues AS $key=>$value){
                if(!array_key_exists($key,$this->filteredFields))
                    continue;
                $query->where($key,'LIKE',$value.'%');
            }
        }

        return $query;

    }

    /**
     * Returns the base query, must be implemented by user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    abstract protected function getQuery():Builder;


    /**
     * Process the query and returns a Resource
     * @return \Illuminate\Http\Resources\Json\Resource
     */
    final private function getResource(){
        $query = $this->getQuery();

        $pagination=null;
        $result = null;

        if($this->paginate){
            $result=$query->simplePaginate($this->paginate);
        }else{
            $result=$query->get();
        }

        return call_user_func([$this->useResource?:\Illuminate\Http\Resources\Json\Resource::class,'collection'],$result);
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
     * @return \Illuminate\Http\JsonResponse
     */
    public final function toResponse($request)
    {
        return $this->getResource()->toResponse($request);
    }

    /**
     * Return the data to be serialized
     */
    public final function jsonSerialize()
    {
        return $this->getResource();
    }

    /**
     * Returns a JSON string if cast to string
     * @return string
     */
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
}