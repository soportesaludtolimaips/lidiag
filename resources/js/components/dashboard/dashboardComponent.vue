<template>
    <div>
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
            <div class="col-md-5 align-self-center">
                <h3 class="text-themecolor">Dashboard</h3>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="javascript:void(0)">Inicio</a>
                    </li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Info box -->
        <!-- ============================================================== -->
        <div class="row">
            <!-- Column -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-success icon-Target-Market"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">2346
                                    <small>
                                        <i class="ti-angle-down text-danger"></i>
                                    </small>
                                </h2>
                                <h6 class="text-muted m-b-0">Total estudios agendados</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <!-- Column -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-info icon-Dollar-Sign"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">$8,759
                                    <small>
                                        <i class="ti-angle-up text-success"></i>
                                    </small>
                                </h2>
                                <h6 class="text-muted m-b-0">Total estudios por transcribir</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <!-- Column -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-primary icon-Inbox-Forward"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">2,356
                                    <small>
                                        <i class="ti-angle-up text-success"></i>
                                    </small>
                                </h2>
                                <h6 class="text-muted m-b-0">Total de estudios leidos</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <!-- Column -->
            <div class="col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex p-10 no-block">
                            <div class="align-self-center display-6 m-r-20">
                                <i class="text-danger icon-Contrast"></i>
                            </div>
                            <div class="align-slef-center">
                                <h2 class="m-b-0">38%
                                    <small><i class="ti-angle-down text-danger"></i>
                                    </small>
                                </h2>
                                <h6 class="text-muted m-b-0">Total de estudios descargados</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <!-- Column -->
        </div>
        <!-- ============================================================== -->
        <!-- End Info box -->
        <!-- ============================================================== -->

        <!-- ============================================================== -->
        <!-- Over Visitor, Our income , slaes different and  sales prediction -->
        <!-- ============================================================== -->
        <div class="row">
            <!-- Column -->
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex m-b-30 no-block">
                            <h5 class="card-title m-b-0 align-self-center">Our Visitors</h5>
                            <div class="ml-auto">
                                <select class="custom-select b-0">
                                    <option selected="">Today</option>
                                    <option value="1">Tomorrow</option>
                                </select>
                            </div>
                        </div>
                        <div id="visitor" style="height:260px; width:100%;"></div>
                        <ul class="list-inline m-t-30 text-center font-12">
                            <li><i class="fa fa-circle text-purple"></i> Tablet</li>
                            <li><i class="fa fa-circle text-success"></i> Desktops</li>
                            <li><i class="fa fa-circle text-info"></i> Mobile</li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <div class="col-lg-4 col-md-12">
                <div class="card o-income">
                    <div class="card-body">
                        <div class="d-flex m-b-30 no-block">
                            <h5 class="card-title m-b-0 align-self-center">Our Income</h5>
                            <div class="ml-auto">
                                <select class="custom-select b-0">
                                    <option selected="">January</option>
                                    <option value="1">February</option>
                                    <option value="2">March</option>
                                    <option value="3">April</option>
                                </select>
                            </div>
                        </div>
                        <div id="income" style="height:260px; width:100%;"></div>
                        <ul class="list-inline m-t-30 text-center font-12">
                            <li><i class="fa fa-circle text-success"></i> Growth</li>
                            <li><i class="fa fa-circle text-info"></i> Net</li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <div class="col-lg-4 col-md-12">
                <div class="row">
                    <!-- Column -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Sales Difference</h5>
                                <div class="d-flex no-block">
                                    <div class="align-self-center no-shrink">
                                        <h2 class="m-b-0">$4316</h2>
                                        <h6 class="text-muted">(150-165 Sales)</h6>
                                    </div>
                                    <div class="ml-auto">
                                        <div id="sales" class="" style=" width:150px; height:140px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Sales Prediction</h5>
                                <div class="d-flex no-block">
                                    <div class="align-self-end no-shrink">
                                        <h2 class="m-b-0">$3528</h2>
                                        <h6 class="text-muted">(150-165 Sales)</h6>
                                    </div>
                                    <div class="ml-auto">
                                        <div id="prediction"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- Sales Chart and browser state-->
        <!-- ============================================================== -->
        <div class="row">
            <!-- Column -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex no-block">
                            <div>
                                <h5 class="card-title m-b-0">Sales Chart</h5>
                            </div>
                            <div class="ml-auto">
                                <ul class="list-inline text-center font-12">
                                    <li><i class="fa fa-circle text-success"></i> SITE A</li>
                                    <li><i class="fa fa-circle text-info"></i> SITE B</li>
                                    <li><i class="fa fa-circle text-primary"></i> SITE C</li>
                                </ul>
                            </div>
                        </div>
                        <div class="" id="sales-chart" style="height: 355px;"></div>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Browser Stats</h5>
                        <table class="table browser m-t-30 no-border">
                            <tbody>
                                <tr>
                                    <td style="width:40px"><img src="/admin-wrap/assets/images/browser/chrome-logo.png"
                                            alt="logo">
                                    </td>
                                    <td>Google Chrome</td>
                                    <td class="text-right">23%</td>
                                </tr>
                                <tr>
                                    <td><img src="/admin-wrap/assets/images/browser/firefox-logo.png" alt="logo"></td>
                                    <td>Mozila Firefox</td>
                                    <td class="text-right">15%</td>
                                </tr>
                                <tr>
                                    <td><img src="/admin-wrap/assets/images/browser/safari-logo.png" alt="logo"></td>
                                    <td>Apple Safari</td>
                                    <td class="text-right">07%</td>
                                </tr>
                                <tr>
                                    <td><img src="/admin-wrap/assets/images/browser/internet-logo.png" alt="logo"></td>
                                    <td>Internet Explorer</td>
                                    <td class="text-right">09%</td>
                                </tr>
                                <tr>
                                    <td><img src="/admin-wrap/assets/images/browser/opera-logo.png" alt="logo"></td>
                                    <td>Opera mini</td>
                                    <td class="text-right">23%</td>
                                </tr>
                                <tr>
                                    <td><img src="/admin-wrap/assets/images/browser/netscape-logo.png" alt="logo"></td>
                                    <td>Netscape Navigator</td>
                                    <td class="text-right">04%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Sales Chart -->
        <!-- ============================================================== -->
    </div>
</template>

<script>

export default {
    mounted() {
        this.estudiosTorta();
    },
    methods: {
        estudiosTorta() {
            var chart = c3.generate({
                bindto: '#visitor',
                data: {
                    columns: [
                        ['Other', 30],
                        ['Desktop', 10],
                        ['Tablet', 40],
                        ['Mobile', 50],
                    ],

                    type: 'donut',
                    onclick: function (d, i) { console.log("onclick", d, i); },
                    onmouseover: function (d, i) { console.log("onmouseover", d, i); },
                    onmouseout: function (d, i) { console.log("onmouseout", d, i); }
                },
                donut: {
                    label: {
                        show: false
                    },
                    title: "Visits",
                    width: 20,

                },

                legend: {
                    hide: true
                    //or hide: 'data1'
                    //or hide: ['data1', 'data2']
                },
                color: {
                    pattern: ['#eceff1', '#24d2b5', '#6772e5', '#20aee3']
                }
            });


            // ============================================================== 
            // Our Income
            // ==============================================================
            var chart = c3.generate({
                bindto: '#income',
                data: {
                    columns: [
                        ['Growth Income', 100, 200, 100, 300],
                        ['Net Income', 130, 100, 140, 200]
                    ],
                    type: 'bar'
                },
                bar: {
                    space: 0.2,
                    // or
                    width: 15 // this makes bar width 100px
                },
                axis: {
                    y: {
                        tick: {
                            count: 4,

                            outer: false
                        }
                    }
                },
                legend: {
                    hide: true
                    //or hide: 'data1'
                    //or hide: ['data1', 'data2']
                },
                grid: {
                    x: {
                        show: false
                    },
                    y: {
                        show: true
                    }
                },
                size: {
                    height: 290
                },
                color: {
                    pattern: ['#24d2b5', '#20aee3']
                }
            });

            // ============================================================== 
            // Sales chart
            // ============================================================== 
            Morris.Area({
                element: 'sales-chart',
                data: [{
                    period: '2011',
                    Sales: 50,
                    Earning: 80,
                    Marketing: 20
                }, {
                    period: '2012',
                    Sales: 130,
                    Earning: 100,
                    Marketing: 80
                }, {
                    period: '2013',
                    Sales: 80,
                    Earning: 60,
                    Marketing: 70
                }, {
                    period: '2014',
                    Sales: 70,
                    Earning: 200,
                    Marketing: 140
                }, {
                    period: '2015',
                    Sales: 180,
                    Earning: 150,
                    Marketing: 140
                }, {
                    period: '2016',
                    Sales: 105,
                    Earning: 100,
                    Marketing: 80
                },
                {
                    period: '2017',
                    Sales: 250,
                    Earning: 150,
                    Marketing: 200
                }
                ],
                xkey: 'period',
                ykeys: ['Sales', 'Earning', 'Marketing'],
                labels: ['Site A', 'Site B', 'Site C'],
                pointSize: 0,
                fillOpacity: 0,
                pointStrokeColors: ['#20aee3', '#24d2b5', '#6772e5'],
                behaveLikeLine: true,
                gridLineColor: '#e0e0e0',
                lineWidth: 3,
                hideHover: 'auto',
                lineColors: ['#20aee3', '#24d2b5', '#6772e5'],
                resize: true

            });


            var chart = c3.generate({
                bindto: '#sales',
                data: {
                    columns: [
                        ['One+', 50],
                        ['T', 60],
                        ['Samsung', 20],

                    ],

                    type: 'donut',
                    onclick: function (d, i) { console.log("onclick", d, i); },
                    onmouseover: function (d, i) { console.log("onmouseover", d, i); },
                    onmouseout: function (d, i) { console.log("onmouseout", d, i); }
                },
                donut: {
                    label: {
                        show: false
                    },
                    title: "",
                    width: 18,

                },
                size: {
                    height: 150
                },
                legend: {
                    hide: true
                    //or hide: 'data1'
                    //or hide: ['data1', 'data2']
                },
                color: {
                    pattern: ['#eceff1', '#24d2b5', '#6772e5', '#20aee3']
                }
            });

            // ============================================================== 
            // Sales Prediction
            // ============================================================== 

            var chart = c3.generate({
                bindto: '#prediction',
                data: {
                    columns: [
                        ['data', 91.4]
                    ],
                    type: 'gauge',
                    onclick: function (d, i) { console.log("onclick", d, i); },
                    onmouseover: function (d, i) { console.log("onmouseover", d, i); },
                    onmouseout: function (d, i) { console.log("onmouseout", d, i); }
                },

                color: {
                    pattern: ['#ff9041', '#20aee3', '#24d2b5', '#6772e5'], // the three color levels for the percentage values.
                    threshold: {
                        //            unit: 'value', // percentage is default
                        //            max: 200, // 100 is default
                        values: [30, 60, 90, 100]
                    }
                },
                gauge: {
                    width: 22,
                },
                size: {
                    height: 120,
                    width: 150
                }
            });
        },
    }
}
</script>

<style></style>