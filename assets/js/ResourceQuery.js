
class ResourceQuery
{
    constructor(url,opt={},data=null)
    {
        this._url    = url;
        this._opt    = {
            filterField : 'filter',//Name of the filter query parameter
            method      : 'get',
        };
        if(opt)
            Object.assign(this._opt,opt);
        this._result = data;
        this._params={
            page:1,
            per_page:null,
            filter:{},
        };


        if(!data)
        {
            let page = /([?&]page=)([\d]+)/.exec(url);
            if(page){
                this._params.page = page[2];
            }
        }else{
            if(data.meta){
                this._params.page       = data.meta.current_page;
                this._params.per_page   = data.meta.per_page;
            }
        }

    }


    orderBy(field,direction){
        this._params.orderBy=field?[field,(direction==='desc'?'desc':'asc')]:null;
    }
    get orderByField(){return this._params.orderBy && this._params.orderBy[0];}
    set orderByField(v){ this._params.orderBy=[v,this.orderByDirection]}
    get orderByDirection(){return this._params.orderBy && this._params.orderBy[1];}
    set orderByDirection(v){this._params.orderBy=[this.orderByField,(v==='desc'?'desc':'asc')]}

    // Return result data
    get data(){ return this._result?this._result.data:null; }

    // Return true if has paging enabled
    hasPaging(){return this._result && this._result.meta && this._result.meta.current_page ;}

    // Number of items per page
    get itemsPerPage(){ return (this.hasPaging()?parseInt(this._result.meta.per_page):this._params.per_page||null); }
    set itemsPerPage(v){ this._params.per_page = v; }

    //Current page
    get currentPage(){return this.hasPaging()? parseInt(this._result.meta.current_page) :(this._params.page|null);}
    set currentPage(v){this.page=v;}

    // Shortand for current page
    get page(){return this.currentPage;}
    set page(v){this._params.page=v;}

    // Total pages present
    get totalPages(){return this.hasPaging() && this._result.meta.last_page?parseInt(this._result.meta.last_page):1;}
    // Total items present
    get totalItems(){return this.hasPaging()?parseInt(this._result.meta.total):(this.data?this.data.length:0);}

    // Returns pagination data
    get pagination(){return this.hasPaging()?this._result.meta:null;}

    hasPrev(){return this.hasPaging() && this._result.links.prev;}
    hasNext(){return this.hasPaging() && this._result.links.next;}

    lastPage(){ return this.hasPaging()?this._result.meta.last_page:null; }


    /*** Filtering ***/
    filter(key,value){
        this._params.filter[key]=value;

        return this;
    }

    unFilter(key){
        delete this._params.filter[key];
        return this;
    }

    set filters(filters){
        this._params.filter = filters;
        return this;
    }
    get filters(){ return this._params.filter;}

    removeFilters(){
        this._params.filter={};
        return this;
    }

    /*** get results ***/

    async query(override=null){
        let url    = this._url;

        let opt = Object.assign({},this._params);

        override && Object.assign(opt,override);


        let params = {
            page:opt.page,
        };

        if(this._params.orderBy){
            params['order_by']=this._params.orderBy[0];
            params['order_by_dir']=this._params.orderBy[1];
        }
        if(this._params.per_page){
            params.per_page=this._params.per_page||this.itemsPerPage;
        }

        if(this._opt.filterField){
            params[this._opt.filterField]=Object.assign({},opt.filter);
        }else{
            Object.assign(params,this._params.filter);
        }
        
        let method = this._opt.method||'get';
        this.cancelToken = axios.CancelToken.source();
        let result = await axios[method](url,method==='get'?{params}:params,{
            cancelToken : this.cancelToken.token,
        });
        this.cancelToken = null;
        return new ResourceQuery(url,this._opt,result.data);
    }

    cancel(){
        if(!this.cancelToken)
            return false;
        this.cancelToken.cancel('Operation canceled by the user.');
        return true;
    }

    async fetch()
    {
        return this.query();
    }


    async getNextPage(){
        return this.getPage(this.currentPage()+1);
    }

    async getPrevPage(){
        return this.getPage(this.currentPage()-1);
    }

    async getPage(page){
        if(!this.hasPaging())
            Error('Resource does not have paging!');
        if(page<1 || page>this.lastPage())
            Error('Page is out of range!');

        return this.query({
            page
        });
    }

    resetResult(){
        this._result=null;
    }
}

export default ResourceQuery;
