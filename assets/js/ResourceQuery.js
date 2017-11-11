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
            page:null,
            filter:{},
        };


        if(!data)
        {
            let page = /([?&]page=)([\d]+)/.exec(url);
            if(page){
                this._params.page = page[2];
            }
        }
        
    }

    get data(){ return this._result?this._result.data:undefined; }

    hasPaging(){return this._result? this._result.meta && this._result.meta.current_page : undefined;}

    currentPage(){return this.hasPaging()?this._result.meta && this._result.meta.current_page && this._result.links:null;}

    hasPrev(){return this.hasPaging() && this._result.links.prev;}
    hasNext(){return this.hasPaging() && this._result.links.next;}

    lastPage(){ return this.hasPaging()?undefined:this._result.meta.last_page; }
    itemsPerPage(){ return this.hasPaging()?undefined:this._result.meta.per_page; }


    /*** Filtering ***/
    filter(key,value){
        this._params.filter[key]=value;
        return this;
    }

    unFilter(key){
        delete this._params.filter[key];
        return this;
    }

    removeFilters(){
        this._params.filter={};
        return this;
    }

    /*** get results ***/

    async query(args={}){
        let url    = this._url;

        let params = {
            page:this._params.page,
        };
        if(this._opt.filterField){
            params[this._opt.filterField]=this._params.filter;
        }else{
            Object.assign(params,this._params.filter);
        }
        //Override params
        if(args)
            Object.assign(params,args);

        let method = this._opt.method||'get';
        console.log('query:',{params,method});

        let result = await axios[method](url,method==='get'?{params}:params);
        return new ResourceQuery(this.url,this._opt,result.data);
    }

    async get()
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
}

export default ResourceQuery;
module.exports = ResourceQuery;