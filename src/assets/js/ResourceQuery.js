class ResourceQuery
{
    constructor(url,opt={},data=null)
    {
        this._url    = url;
        this._opt    = opt;
        this._result = data;
        this.params={
            page:null,
            filter:null,
        };

        if(!data)
        {
            let page = /([?&]page=)([\d]+)/.exec(url);
            if(page){
                this.params.page = page[2];
            }
        }
    }

    data(){return this._result.data;}
    get data(){return this.data()};

    hasPaging(){return this._result.meta && this._result.meta.current_page;}
    get hasPaging(){return this.hasPaging();}

    currentPage(){return this.hasPaging()?this._result.meta && this._result.meta.current_page && this._result.links:null;}
    get currentPage(){return this.currentPage();}


    hasPrev(){return this.hasPaging() && this._result.links.prev;}
    get hasPrev(){return this.hasPrev;}

    hasNext(){return this.hasPaging() && this._result.links.next;}
    get hasNext(){return hasNext;}

    lastPage(){ return this.hasPaging()?undefined:this._result.meta.last_page; }
    get lastPage(){return this.lastPage();}

    itemsPerPage(){ return this.hasPaging()?undefined:this._result.meta.per_page; }
    get itemsPerPage(){return this.itemsPerPage();}


    /*** Filtering ***/
    filter(key,value){
        this.params.filter[key]=value;
        return this;
    }

    unFilter(key){
        delete this.params.filter[key];
        return this;
    }

    removeFilters(){
        this.params.filter={};
        return this;
    }

    /*** get results ***/

    async query(params={}){

        let url    = this._url;
        params = Object.assign({},this.params,params);
        let method = this._opt.method||'get';

        let result = await axios({
            url,
            method,
            data:method=='get'?{params}:params;
        });
        return new QueryResult(this.url,this._opt,result);
    }

    async get()
    {
        return this.query();
    }


    async getNextPage(){
        return this.getPage(this.currentPage()+1);
    }

    async getPrevPage(){
        return this.getPage(currentPage-1);
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