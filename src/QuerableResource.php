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


    abstract protected function getQuery():Builder;

    ///


    final private function getData(){
        //TODO:JUST A METHOD STUB!

        $query = $this->getQuery();
        if($this->paginate){
            $query->paginate($this->paginate);
        }

        $result = $query->get();
        if($this->useResource) {
            $result = call_user_func([$this->useResource,'collection'],$result);
        }

        return $result;
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
        return response()->json($this);
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
        return $this->getData();
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
}