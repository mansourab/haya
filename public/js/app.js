// JQuery inputFile name

$('.custom-file-input').on('change', function(e) {
    var inputFile = e.currentTarget;

    $(inputFile).parent()
        .find('.custom-file-label')
        .html(inputFile.files[0].name);
})

let Filter = require('./search/Filter.js');



 
