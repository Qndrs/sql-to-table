document.addEventListener('DOMContentLoaded', function () {
    var textareas = document.querySelectorAll('textarea.sql-query');
    textareas.forEach(function (textarea) {
        CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            mode: "sql"
        }).setSize(400, 100);
    });
});
