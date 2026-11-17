(function () {
    'use strict';
    window.downloadReportExcel = function (dataId, filename, sheetName) {
        if (typeof XLSX === 'undefined') {
            window.alert('Excel export could not load. Please reload the report.');
            return;
        }
        const data = JSON.parse(document.getElementById(dataId).textContent);
        const workbook = XLSX.utils.book_new();
        const sheet = XLSX.utils.aoa_to_sheet([data.headers, ...data.rows]);
        for (let row = 1; row <= data.rows.length; row++) {
            for (const column of data.numberColumns || []) {
                const cell = sheet[XLSX.utils.encode_cell({r: row, c: column})];
                if (cell && cell.t === 'n') cell.z = '#,##0.00';
            }
        }
        sheet['!cols'] = data.headers.map((header, column) => ({wch: Math.min(45, Math.max(16, String(header).length + 3))}));
        XLSX.utils.book_append_sheet(workbook, sheet, sheetName);
        if (data.summary && data.summary.length) {
            XLSX.utils.book_append_sheet(workbook, XLSX.utils.aoa_to_sheet(data.summary), 'Summary');
        }
        XLSX.writeFile(workbook, filename, {bookType: 'xlsx'});
    };
}());
