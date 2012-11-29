var Diehard = Class.create();
Diehard.prototype =
{
    initialize: function(url, action) {
        this.url = url;
        this.action = action;
        this.params = {};
        this.blocks = {};
        this.defaultIgnored = [];
        document.observe('dom:loaded', this.loadDynamicContent.bind(this));
    },

    setParams: function(params) {
        this.params = params;
    },

    setBlocks: function(blocks) {
        this.blocks = blocks;
    },

    setDefaultIgnoredBlocks: function(blocks) {
        this.defaultIgnored = blocks;
    },

    loadDynamicContent: function() {
        // Remove ignored blocks
        var ignored = Mage.Cookies.get('diehard_ignored');
        if (ignored === null) { // No cookie means user has only hit cached pages thus far
          ignored = this.defaultIgnored; // Use all default ignored blocks as ignored blocks
        } else if (ignored == '-') { // '-' is a sentinel value for no blocks
          ignored = [];
        } else { // Otherwise, if cookie is present then only ignore blocks that are in the cookie
          ignored = ignored.split(',');
        }
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
                method: 'get',
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
        var matches = $$(block.key);
        if(matches.length) { matches[0].replace(block.value); }
    });
};
