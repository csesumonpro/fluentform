<template>
    <div class="ff_report_card">
        <div class="report_header">
            <div class="title">{{report.label}}</div>
            <btn-group as="div" class="ff_chart_switcher" size="sm">
                <btn-group-item as="div">
                    <span @click="chartType = 'pie-chart'" :class="(chartType == 'pie-chart') ? 'active_chart' : ''"
                      class="dashicons dashicons-chart-pie"></span>
                </btn-group-item>
                <btn-group-item as="div">
                       <span @click="chartType = 'bar-chart'" :class="(chartType == 'bar-chart') ? 'active_chart' : ''"
                      class="dashicons dashicons-chart-bar"></span>
                </btn-group-item>
                <btn-group-item as="div">
                     <span @click="chartType = 'horizontal-bar'"
                      :class="(chartType == 'horizontal-bar') ? 'active_chart' : ''"
                      class="dashicons ff_rotate_90 dashicons-chart-bar"></span>
                </btn-group-item>
            </btn-group>
        </div>
        <div class="report_body">
            <div class="ff_chart_view">
                <comment :is="chartType" :chartdata="chartData" :options="chartOptions"></comment>
            </div>
            <div class="chart_data">
                <table class="ff-table">
                    <thead>
                    <tr>
                        <th>{{ $t('Label') }}</th>
                        <th>{{ $t('Total') }}</th>
                        <th>%</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(reportItem,reportIndex) in tableData" :key="reportIndex">
                        <td>{{getLabel(reportItem.value, true)}}</td>
                        <td>{{reportItem.count}}</td>
                        <td>{{ parseInt(parseInt(reportItem.count) / parseInt(report.total_entry) * 100) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="report_footer">

        </div>
    </div>
</template>

<script type="text/babel">
    import PieChart from './_PieChart';
    import BarChart from './_BarChart';
    import HorizontalBar from './_HorizontalBarChart';
    import chroma from 'chroma-js'
    import each from 'lodash/each';
    import truncate from 'lodash/truncate';
    import BtnGroup from '@/admin/components/BtnGroup/BtnGroup.vue';
    import BtnGroupItem from '@/admin/components/BtnGroup/BtnGroupItem.vue';

    export default {
        name: 'report_card',
        components: {
            PieChart,
            BarChart,
            HorizontalBar,
            BtnGroup,
            BtnGroupItem
        },
        data() {
            return {
                chartType: 'pie-chart'
            }
        },
        computed: {
            chartData() {
                let dataItems = {
                    labels: [],
                    data: []
                };
                let dataLength = 0;
                each(this.report.reports, (item) => {
                    dataLength += 1;
                    let itemLabel = this.getLabel(item.value);
                    dataItems.labels.push(itemLabel);
                    dataItems.data.push(item.count);
                });

                return {
                    labels: dataItems.labels,
                    datasets: [{
                        label: '# of Records',
                        data: dataItems.data,
                        backgroundColor: this.getColors(dataLength, 'normal'),
                        borderColor: this.getColors(dataLength, 'solid'),
                        borderWidth: 1
                    }]
                };
            },
            chartOptions() {
                if (this.chartType == 'pie-chart') {
                    return {
                        legend: {
                            display: true,
                            labels: {
                                fontColor: 'rgb(0, 0, 0)'
                            },
                            position: 'bottom'
                        }
                    }
                } else if (this.chartType == 'bar-chart') {
                    return {
                        legend: {
                            display: true,
                            labels: {
                                fontColor: 'rgb(0, 0, 0)'
                            },
                            position: 'bottom'
                        },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                } else if (this.chartType == 'horizontal-bar') {
                    return {
                        legend: {
                            display: true,
                            labels: {
                                fontColor: 'rgb(0, 0, 0)'
                            },
                            position: 'bottom'
                        },
                        scales: {
                            xAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                }
                return {}
            },
            tableData() {
                return this.report.reports.sort((a, b) => (parseInt(a.count) < parseInt(b.count)) ? 1 : -1);
            },
            chartKey() {
                return 'chart_'+this.form_id+'_'+this.report_key;
            }
        },
        watch: {
            chartType() {
                this.$storeData(this.chartKey, this.chartType);
            }
        },
        methods: {
            getColors(times, type) {

                let colorScheams = [
                    ['#1a7efb', '#673AB7'],
                    ['#a32f80', '#ff5858'],
                    ['#595F36', '#C7D482'],
                    ['#B6583B', '#EDAE9B'],
                    ['#590d82', '#f25d9c'],
                    ['#d4a5a5', '#a6d0e4'],
                    ['#5F5771', '#978BB4'],
                    ['#f38181', '#bfcfff'],
                    ['#1f5f8b', '#1891ac'],
                    ['#de95ba', '#4a266a'],
                    ['#086972', '#17b978']
                ];

                let colorPair = colorScheams[this.report_indexes.indexOf(this.report_key)];

                if(!colorPair) {
                     colorPair = colorScheams[Math.floor(Math.random()*colorScheams.length)];
                }

                if (type == 'solid') {
                    return chroma.scale(colorPair)
                        .mode('lch').colors(times);
                }
                return chroma.scale(colorPair)
                    .mode('lch').colors(times);
            },
            getLabel(itemValue, noTruncate) {
                let label = (this.report.options && this.report.options[itemValue]) ? this.report.options[itemValue] : itemValue;
                if (noTruncate) {
                    return label;
                }
                return truncate(label, {
                    'length': 20
                });
            }
        },
        props: ['report', 'report_key', 'report_indexes', 'form_id'],
        created() {
            this.chartType = this.$getFromStore(this.chartKey, 'pie-chart');
        }
    }
</script>


