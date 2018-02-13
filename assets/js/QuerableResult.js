import ResourceQuery from './ResourceQuery';

class QuerableResult {
    constructor(url){
        this.query = new ResourceQuery(url);

        this.ready = false;
        this.error=null;
        this._page=1;
        this.filters={};
        this.debounce=200;

        this.init=false;
    }


    get data(){
        if(!this.init){
            this.init = true;
            this.fetchImmediate();//Auto fetch
        }
        return this.query.data
    }

    hasError(){return this.error;}

    get totalPages(){return this.query.totalPages;}
    get totalItems(){return this.query.totalItems;}

    get itemsPerPage(){return this.query.itemsPerPage;}
    set itemsPerPage(v){
        if(this.query.itemsPerPage===v){
            console.log('itemsPerPage ('+this.query.itemsPerPage+')already',v)
            return;
        }

        this.query.itemsPerPage=v;
        this.fetch();
    }


    get pagination(){return this.query.pagination;}

    set page(v){
        if(this.query.page===v)
            return;
        this.query.page=v;
        this.fetch();
    }
    get page(){
        return this.query.page;
    }

    paginate(num){
        this.query.paginate(num);
        this.fetch();
    }

    filter(key,value){
        this.filters=Object.assign({},this.filters,{[key]:value});
        this.query.filters(this.filters);
        this.fetch();
    }
    set filters(filters){
        this.query.filters = filters;
        this.fetch();
    }
    get filters(){return this.query.filters}

    orderBy(field,direction){
        if(field!==this.orderByField || direction!== this.orderByDirection){
            this.query.orderBy(field,direction);
            this.fetch();
        }
    }

    get orderByField(){return this.query.orderByField}
    set orderByField(v){this.query.orderByField=v;}
    get orderByDirection(){return this.query.orderByDirection}
    set orderByDirection(v){this.query.orderByDirection=v;}


    async fetch(){
        //Clear timer if present
        this._debounce_timer && clearTimeout(this._debounce_timer);

        if(this.debounce>0){
            let resolve=null,abort=null;
            let promise = new Promise((r,a)=>{ resolve=r;abort=a;});


            this._debounce_timer=setTimeout(()=>{
                    this._debounce_timer=null;
                    try{
                        resolve(this.fetchImmediate());
                    }catch(e){
                        abort(e);
                    }
                }, this.debounce);

        }
        else{
            //Immediate
            return this.fetchImmediate();
        }
    }

    async fetchImmediate(){
        // _.debounce(()=>{
        console.log('Qr fetch...');
        this.ready = false;
		this.error = null;

        try{
            let r = await this.query.fetch();

            this.ready = true;
            this.error = null;
            this.query = r;

            return this;
        }catch(e){
            console.error('QuerableResult fetching error:',e);
            this.ready = true;
            this.error = e;
            throw e;
        }
    }

    resetData(){
        this.query.resetResult();
    }

}

export {ResourceQuery};
export default QuerableResult;