(function(global, undefined){
    function Contacts(opts){
        if(!(this instanceof Contacts)){
            return new Contacts(opts);
        }
        this.merge(this.opts, opts);
        this.init();
    }

    Contacts.prototype = {
        opts: {
            appendTo: '',
            generateListItem: null,
            data: []
        },
        merge: function(defaultOpts, userOpts){
            if(userOpts){
                Object.assign(defaultOpts, userOpts);
            }
        },
        init: function(){
            this.parseData();
            this.generateShortcuts();

            var list = this.generateList();
            var ctn = this.generateCtn();
            ctn.querySelector('.all-result').appendChild(list);
            var appendTo = this.opts.appendTo || 'body';
            document.querySelector(appendTo).appendChild(ctn);
            this.getAllAnchorPositions();

        },
        parseData: function(){
            var self = this;
            var data = this.opts.data;
            var map = {};
            var c = 'A'.charCodeAt();
            for(; c <= 'Z'.charCodeAt(); c++ ){
                map[String.fromCharCode(c)] = [];
            }
            map['#'] = [];
            var firstCharUpper;
            data.forEach(function(item){
                firstCharUpper = self.getFirstUpperChar(item.name);
                if (map.hasOwnProperty(firstCharUpper)) {
                    map[firstCharUpper].push(item);
                } else {
                    map['#'].push(item);
                }
            });

            //排序
            for(var key in map) {
                if( map.hasOwnProperty( key ) && (map[key].length != 0)) {
                    map[key].sort(function(a, b){
                        return a.name.localeCompare(b.name, 'zh-CN-u-co-pinyin');
                    });
                }
            }

            this.dictMap = map;
            return map;
        },
        generateShortcuts: function(){
            var items = [];
            var map = this.dictMap;
            for(var key in map) {
                if( map.hasOwnProperty( key ) && (map[key].length != 0)) {
                    items.push(key);
                }
            }
            var ctn = document.createElement('div');
            ctn.classList.add('shortcuts-ctn');
            var test,a;
            items.forEach(function(item){
                text = document.createTextNode(item);
                a = document.createElement('a');
                a.setAttribute('href', '#hook-' + item);
                a.setAttribute('rel', 'internal');
                a.appendChild(text);
                ctn.appendChild(a);
            });
            document.body.appendChild(ctn);
        },
        generateList: function(){
            var self = this;
            var map = self.dictMap;
            var formerKey = null;
            var list = document.createElement('ul');
            list.classList.add('list');
            for(var key in map) {
                if( map.hasOwnProperty( key ) && (map[key].length != 0)) {
                    var items = map[key];
                    items.forEach(function(item){
                        var text,li,a;
                        if(key != formerKey){
                            a = document.createElement('a');
                            a.setAttribute('id', 'hook-' + key);
                            text = document.createTextNode(key);
                            li = document.createElement('li');
                            li.classList.add('hooks');
                            li.appendChild(a);
                            li.appendChild(text);
                            list.appendChild(li);
                            formerKey = key;
                        }
                        list.appendChild(self.generateListItem(item));
                    });
                }
            }
            return list;
        },
        generateListItem: function(item){
            if(this.opts.generateListItem && typeof this.opts.generateListItem == 'function'){
                return this.opts.generateListItem(item);
            }
            var tpl = '<div class="logo"><img src="' + item.logo +'"></img></div><p class="name">'+ item.name + '</p><p class="url">'+ item.url + '</p>';
            var li = document.createElement('li');
            li.innerHTML = tpl;
            return li;
        },
        generateCtn: function(){
            var ctn = document.createElement('div');
            ctn.classList.add('container');

            var searchResult = document.createElement('div');
            searchResult.classList.add('search-result');
            ctn.appendChild(searchResult);
            var allResult = document.createElement('div');
            allResult.classList.add('all-result');
            ctn.appendChild(allResult);
            return ctn;
        },

        generateFilteredList: function(map, filter_str){
            var list = document.createElement('ul');
            list.classList.add('list');
            var li;
            for( var key in map){
                if( map.hasOwnProperty( key ) && (map[key].length != 0)) {
                    var items = map[key];
                    items.forEach(function(item){
                        if (String(item.name).match(filter_str)) {
                            li = document.createElement('li')
                            li.appendChild(document.createTextNode(item.name));
                            list.appendChild(li);
                        }
                    });
                }
            }
            return list;
        },
        getFirstUpperChar: function(str){
            string = String(str);
            var c = string[0];
            if (/[^\u4e00-\u9fa5]/.test(c)) {
                return c.toUpperCase();
            }
            else {
                return this.chineseToEnglish(c);
            }
        },

        chineseToEnglish: function(c){
            var idx = -1;
            var MAP = 'ABCDEFGHJKLMNOPQRSTWXYZ';
            var boundaryChar = '驁簿錯鵽樲鰒餜靃攟鬠纙鞪黁漚曝裠鶸蜶籜鶩鑂韻糳';
            if (!String.prototype.localeCompare) {
                throw Error('String.prototype.localeCompare not supported.');
            }
            if (/[^\u4e00-\u9fa5]/.test(c)) {
                return c;
            }
            for (var i = 0; i < boundaryChar.length; i++) {
                if (boundaryChar[i].localeCompare(c, 'zh-CN-u-co-pinyin') >= 0) {
                    idx = i;
                    break;
                }
            }
            return MAP[idx];
        },
        getAllAnchorPositions: function(){
            var anchors = document.querySelectorAll('.hooks');
            var self = this;
            self.positions = [];
            anchors = [].slice.call(anchors);

            anchors.forEach(function(anchor){
                self.positions.push({
                    anchor: anchor,
                    pos: anchor.getBoundingClientRect().top
                });
            });
        },
        getTopbarElement: function(scrollPosition){
            var i = 0;
            var gutter = 20;
            while((i < this.positions.length) && scrollPosition + gutter >= this.positions[i].pos){
                i ++;
            }


            if (i == 0) {
                return null;
            }
            else {
                return this.positions[i-1].anchor;
            }
        }
    };

    global.Contacts = Contacts;
})(window);

