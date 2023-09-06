// // switched to entwine for dynamic initiation
// (function($) {
//     $.entwine(function($) {
//         $('input.autocomplete-text').entwine({
//             onmatch: function() {
//                 this._super();
//
//                 $(this).devbridgeAutocomplete( $(this).data('jsconfig') );
//                 $(this).devbridgeAutocomplete().setOptions({'onSelect':function(){ $(this).trigger('change'); }});
//                 $(this).devbridgeAutocomplete().setOptions({'autoFocus':true});
//             }
//         });
//     });
// })(jQuery);

(function($) {
    $('input.autocomplete-text').each(function(){
        $(this).devbridgeAutocomplete( $(this).data('jsconfig') );
        $(this).devbridgeAutocomplete().setOptions({'onSelect':function(){ $(this).trigger('change'); }});
        $(this).devbridgeAutocomplete().setOptions({'autoFocus':true});
    });
})(jQuery);