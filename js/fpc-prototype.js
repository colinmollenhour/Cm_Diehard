var Diehard = Class.create();
Diehard.prototype =
{
    initialize: function(url, action) {
        this.url = url;
        this.action = action;
        this.params = {};
        this.blocks = {};
        document.observe('dom:loaded', this.loadDynamicContent.bind(this));
    },

    setParams: function(params) {
        this.params = params;
    },

    setBlocks: function(blocks) {
        this.blocks = blocks;
    },

    loadDynamicContent: function() {
        // Remove ignored blocks
        var ignored = Mage.Cookie.get('diehard_ignored').split(',');
        this.blocks = $H(this.blocks).inject({}, function(acc, pair){
            if( ! ignored.member(pair.value)) {
                acc[pair.key] = pair.value;
            }
            return acc;
        });

        // Fetch dynamic content
        if($H(this.blocks).keys().length) {
            var params = {
                full_action_name: this.action,
                blocks: this.blocks,
                params: this.params
            };
            new Ajax.Request(this.url, {
                parameters: {json: Object.toJSON(params)},
                evalJSON: 'force',
                onSuccess: function(response) {
                    Diehard.replaceBlocks(response.responseJSON);
                }
            });
        }
    }
};
Diehard.replaceBlocks = function(data) {
    $H(data.blocks).each(function(block){
        var el = $(block.key);
        if(el) { el.replace(block.value); }
    });
};
