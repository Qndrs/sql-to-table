document.addEventListener('click', function (event) {
    var button = event.target.closest('.sql-to-table-export');

    if (!button) {
        return;
    }

    var data = JSON.parse(button.dataset.sqlToTableExport || '[]');
    var blob = new Blob([JSON.stringify(data, null, 2)], {
        type: 'application/json'
    });
    var link = document.createElement('a');

    link.href = URL.createObjectURL(blob);
    link.setAttribute('download', 'data.json');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
});
