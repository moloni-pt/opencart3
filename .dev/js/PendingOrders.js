if (pt === undefined) {
    var pt = {};
}

if (pt.moloni === undefined) {
    pt.moloni = {};
}

if (pt.moloni.PendingOrders === undefined) {
    pt.moloni.PendingOrders = {};
}

pt.moloni.PendingOrders = (function ($) {
    var getOrdersAction;
    var bulkOrdersAction;

    var checkMaster;
    var actionButton;
    var datatable;

    function init(_getOrdersAction, _bulkOrdersAction) {
        getOrdersAction = _getOrdersAction;
        bulkOrdersAction = _bulkOrdersAction;

        startObservers();
    }

    function startObservers() {
        datatable = $('#moloni_orders');
        checkMaster = $('.select-all')

        datatable
            .on('preXhr.dt', disableTable)
            .dataTable({
                "processing": true,
                "serverSide": true,
                "bStateSave": true,
                "ajax": {
                    "url": getOrdersAction,
                    "data": {
                        "ajax": true,
                    }
                },
                "columns": [
                    {
                        orderable: false,
                        render: renderCheckbox,
                    },
                    {
                        data: 'info.id_order',
                        orderable: true,
                        render: renderOrderCol,
                    },
                    {
                        data: 'address',
                        orderable: false,
                        render: renderClientCol
                    },
                    {
                        data: 'customer.email',
                        defaultContent: '',
                        orderable: false,
                    },
                    {
                        data: 'info.date_add',
                        orderable: true,
                    },
                    {
                        data: 'state.name',
                        orderable: false,
                    },
                    {
                        data: 'info.total_paid',
                        orderable: true,
                        render: renderPriceCol
                    },
                    {
                        data: 'acts',
                        orderable: false,
                        render: renderActionsCol
                    }
                ],
                "columnDefs": [
                    {
                        className: "dt-center",
                        targets: [1, 7]
                    },
                    {
                        className: "dt-right",
                        targets: 6
                    },
                ],
                "fnDrawCallback": function () {
                    onTableRender();
                },
                "lengthMenu": [10, 25, 50, 75, 100, 250],
                "pageLength": 10,
            });

        var selected = $('.selected');

        $("#create-multiple-invoice").click(function () {
            if (selected.length === 0) {
                alert("Não tem encomendas seleccionadas");
            } else {
                var orders = [];

                $('.selected').each(function (i, order) {
                    orders.push(parseInt(order.attributes.order_id.value));
                });

                window.location.replace(bulkOrdersAction + JSON.stringify(orders));
            }
        });

        $("#mark-as-sent").click(function () {
            if (selected.length === 0) {
                alert("Não tem encomendas seleccionadas");
            } else {
                var orders = [];

                $('.selected').each(function (i, order) {
                    orders.push(parseInt(order.attributes.order_id.value));
                });

                window.location.replace(bulkOrdersAction + JSON.stringify(orders) + "&action=delete");
            }
        });
    }

    //       PRIVATES       //

    function disableTable() {
        datatable.addClass('dataTable--disabled');
    }

    function enableTable() {
        datatable.removeClass('dataTable--disabled');
    }

    function onTableRender() {
        enableTable();

        // todo:
    }

    //       RENDERS       //

    function renderActionsCol(data, type, row, meta) {
        var html = "";

        html += "<a class='moloni-icon' href='" + row.url.create + "'>";
        html += "   <i class='moloni-icon__blue material-icons'>note_add</i>";
        html += "</a>";
        html += "<a class='moloni-icon' href='" + row.url.clean + "'>";
        html += "   <i class='moloni-icon__red material-icons'>delete</i>";
        html += "</a>";

        return html;
    }

    function renderClientCol(data, type, row, meta) {
        var html = "";

        html += "<b>" + data.firstname + " " + data.lastname + "</b>";
        html += "<br>";
        html += "<span style='font-size: 10px'>";

        if (data.address1) {
            html += data.address1 + "<br>";
        }

        if (data.vat_number) {
            html += data.vat_number + "<br>";
        }

        html += "</span> ";

        return html;
    }

    function renderOrderCol(data, type, row, meta) {
        var html = "";

        html += "<a target='_blank' href='" + row.url.order + "'>";
        html += "    #" + data;
        html += "</a>";

        return html;
    }

    function renderPriceCol(data, type, row, meta) {
        var html = "";
        var symbol = "€";

        html += "<div>";
        html += parseFloat(data).toFixed(2);

        if (row && row.currency && row.currency.symbol) {
            symbol = row.currency.symbol;
        }

        html += symbol;
        html += "</div>";

        return html;
    }

    function renderCheckbox(data, type, row, meta) {
        return '<input ' +
            'type="checkbox" ' +
            'name="checkbox" ' +
            'class="order_doc" ' +
            'id="order_doc_' + row.info.id_order + '" ' +
            'value="' + row.info.id_order + '"' +
            '>';
    }

    return {
        init: init,
    }
}(jQuery));

