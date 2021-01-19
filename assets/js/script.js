window.Wooxml = window.Wooxml || {};

// debugging
function pp(variable) { console.log(variable); }

(function (window, document, $, wooxml, undefined) {
    
    // global variables
    let $document;

    wooxml.init = function () {

        $document = $(document);

        wooxml.trigger('wooxml_pre_init');

        // Bind an event
        $('#xml_output').on('click', () => { makeDelay(5)( wooxml.outputXml ); });

        wooxml.trigger('wooxml_init');

    };


    wooxml.outputXml = function () {

        var data = {
            action : "wooxml_output_with_button", 
            nonce : wooxmlOpts.nonce, 
        }

        jQuery.ajax({
            url :       wooxmlOpts.ajax_url,
            type :      'post',
            dataType:   'json',
            data : data,
            complete : function( response ) {
                pp(response)
            },
            success : function( response ) {

                const url = response.results.url;

                const alert = '<div class="notice notice-success is-dismissible">' +
                    '<p>' + url + '</p>' +
                '</div>';

                $( '#wooxml_settings_section' ).append( alert );

                // create the table
                // const head = '<thead><tr><td class="num"></td><td class="name">Horse</td><td class="win">Win</td><td class="place">Place</td><td></td></tr></thead>'
                // const rows = wooxml.arrayToTable( response.results.horses )
                // $('#event_table').val( '<table class="event-table">' + head + rows + '</table>' );
                
                //pp(wooxml.arrayToTable( response.results.horses ))
            }

        });

    };
    
    
    function makeDelay(ms) {
        let timer = 0;
        return function (callback) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    }

    wooxml.trigger = function (evtName) {
        const args = Array.prototype.slice.call(arguments, 1);
        args.push(wooxml);
        $document.trigger(evtName, args);
    };

    wooxml.triggerElement = function ($el, evtName) {
        const args = Array.prototype.slice.call(arguments, 2);
        args.push(wooxml);
        $el.trigger(evtName, args);
    };

    $(wooxml.init);

}(window, document, jQuery, window.Wooxml));
