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
        this._removeIgnoredBlocks();
        this._fetchDynamicContent();
    },

    /**
     * Few cases for ignored blocks:
     *
     * 1. No cookie means user has only hit cached pages thus far.
     *    In this case, we'll use all default ignored blocks as
     *    ignored blocks
     *
     * 2. '-' is a sentinal value for no blocks
     *
     * 3. If a cookie is present, then only ignore the blocks
     *    that are in the cookie.
     */
    _getIgnoredBlocks: function()
    {
        var ignoredCookie = Mage.Cookies.get('diehard_ignored');

        if (ignoredCookie === null) {
            return this.defaultIgnored;
        } else if (ignoredCookie == '-') {
            return [];
        } else {
            return ignoredCookie.split(',');
        }
    },

    _removeIgnoredBlocks: function()
    {
        var ignored = this._getIgnoredBlocks();
        this.blocks = $H(this.blocks).inject({}, function(acc, pair){
            if ( ! ignored.member(pair.value)) {
                acc[pair.key] = pair.value;
            }
            return acc;
        });

        return this;
    },

    _fetchDynamicContent: function()
    {
        if ($H(this.blocks).keys().length) {
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

        return this;
    }
};

Diehard.replaceBlocks = function(data) {
    $H(data.blocks).each(function(block){
        var matches = $$(block.key);
        if (matches.length) { matches[0].replace(block.value); }
    });
    document.fire('diehard:load', {data: data});
};
