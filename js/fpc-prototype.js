var FullPageCache = Class.create();
FullPageCache.prototype =
{
    initialize: function(url, action) {
        this.url = url;
        this.action = action;
        this.params = {};
        this.blocks = {};
        document.observe('dom:loaded', this.loadDynamicContent.bind(this));
    },

    setParam: function(key, value) {
        this.params[key] = value;
    },

    addBlock: function(htmlId, nameInLayout) {
        this.blocks[htmlId] = nameInLayout;
    },

    loadDynamicContent: function() {
        // Add blocks based on class name
        $$('.placeholder').each(function(el) {
          this.addBlock(el.id, el.readAttribute('rel'));
        }.bind(this));

        // Remove ignored blocks
        var ignored = Mage.Cookie.get('static_ignored').split(',');
        this.blocks = $H(this.blocks).inject({}, function(acc, pair){
            if( ! ignored.member(pair.value)) {
                acc[pair.key] = pair.value;
            }
            return acc;
        });

        // Fetch dynamic content
        if($H(this.blocks).keys().length) {
            this.params['blocks'] = this.blocks;
            new Ajax.Request(this.url, {
                parameters: {json: Object.toJSON(this.params)},
                evalJSON: 'force',
                onSuccess: function(response) {
                    FullPageCache.replaceBlocks(response.responseJSON);
                }.bind(this)
            });
        }
    }
};
FullPageCache.replaceBlocks = function(data) {
    $H(data.blocks).each(function(block){
        var el = $(block.key);
        if(el) { el.replace(block.value); }
    });
};
