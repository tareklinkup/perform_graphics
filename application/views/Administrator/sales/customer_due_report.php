<style>
    .v-select {
        margin-bottom: 5px;
    }

    .v-select .dropdown-toggle {
        padding: 0px;
    }

    .v-select input[type=search],
    .v-select input[type=search]:focus {
        margin: 0px;
    }

    .v-select .vs__selected-options {
        overflow: hidden;
        flex-wrap: nowrap;
    }

    .v-select .selected-tag {
        margin: 2px 0px;
        white-space: nowrap;
        position: absolute;
        left: 0px;
    }

    .v-select .vs__actions {
        margin-top: -5px;
    }

    .v-select .dropdown-menu {
        width: auto;
        overflow-y: auto;
    }
</style>

<div class="row" id="customerDueList">
    <div class="col-xs-12 col-md-12 col-lg-12" style="border-bottom:1px #ccc solid;">
        <div class="form-group">
            <label class="col-xs-2 col-lg-1 control-label no-padding-right">Customer</label>
            <div class="col-xs-6 col-lg-3">
                <v-select v-bind:options="customers" v-model="selectedCustomer" label="display_name" placeholder="Select customer" @input="changeCustomer"></v-select>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-2">
                <input type="date" v-model="fromDate" class="form-control"/>
            </div>
            <div class="col-lg-2">
                <input type="date" v-model="toDate" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <div class="col-xs-4 col-lg-2">
                <input type="button" class="btn btn-primary" value="Show Report" v-on:click="getDues" style="margin-top:0px;border:0px;height:28px;outline:none;">
            </div>
        </div>
    </div>

    <div class="col-md-12" style="display: none" v-bind:style="{display: dues.length > 0 ? '' : 'none'}">
        <a href="" style="margin: 7px 0;display:block;width:50px;" v-on:click.prevent="print">
            <i class="fa fa-print"></i> Print
        </a>
        <div class="table-responsive" id="reportTable">
            <div style="overflow-x:auto;">
                <table v-if="dues.length > 0" class="table table-bordered">
                    <tr style="background: rgb(89 138 167);font-weight: 600;">
                        <th style="padding-top: 15px;width:40px;" rowspan="2">SL</th>
                        <th style="padding-top: 15px;width:140px;" rowspan="2">Customer Name</th>
                        <th style="padding-top: 15px;width:110px;" rowspan="2">Customer Mobile</th>
                        <td colspan="2" v-for="item in dues[0].data" >{{item.month_name}}</td>
                        <th style="padding-top: 15px;" rowspan="2">Balance</th>
                    </tr>
                    <tr>
                        <template v-for="index in dues[0].data">
                            <th>Due</th>
                            <th>Paid</th>
                        </template>
                    </tr>
                    <tr v-for='due in dues'>
                        <td>{{ due.Customer_SlNo }}</td>
                        <td>{{ due.Customer_Name }}</td>
                        <td>{{ due.Customer_Mobile }}</td>
                        <template v-for='item2 in due.data'>
                            <td>{{item2.due}}</td>
                            <td>{{item2.paid}}</td>
                        </template>
                        <template v-for='index in (maxLen - due.data.length)'>
                            <td>0.00</td>
                            <td>0.00</td>
                        </template>
                        <td style="font-weight: bold;">{{(due.data.reduce((acc, pre) => {return acc + parseFloat(pre.due)},0) - due.data.reduce((acc, pre) => {return acc + parseFloat(pre.paid)},0)).toFixed(2)}}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/js/vue/vue.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/vue/axios.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/vue/vue-select.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/moment.min.js"></script>

<script>
    Vue.component('v-select', VueSelect.VueSelect);
    new Vue({
        el: '#customerDueList',
        data() {
            return {
                searchType: 'all',
                customers: [],
                selectedCustomer: {
                    Customer_SlNo: "",
                    display_name: "All"
                },
                dues: [],
                maxLen: 0,
                fromDate: moment(new Date()).format("YYYY-MM-DD"),
                toDate: moment(new Date()).format("YYYY-MM-DD")
            }
        },
        created() {
            this.getCustomers();
        },
        methods: {
            getCustomers() {
                axios.get('/get_customers').then(res => {
                    this.customers = res.data;
                    this.customers.unshift({Customer_SlNo: "", display_name: "All"})
                })
            },
            changeCustomer(){
                if (this.selectedCustomer.Customer_SlNo) {
                    this.dues = []
                }
            },
            getDues() {
                this.dues = [];
                this.maxLen = 0
                if (this.searchType == 'customer' && this.selectedCustomer == null) {
                    alert('Select customer');
                    return;
                }
                let data = {
                    customerId: this.selectedCustomer.Customer_SlNo == "" ? null : this.selectedCustomer.Customer_SlNo,
                    fromDate: this.fromDate,
                    toDate: this.toDate,
                };

                axios.post('/getCustomerDueReport', data).then(res => {
                    // console.log(res.data);
                    // return
                    this.dues = res.data
                    res.data.forEach(ele=> {
                        let len = ele.data.length;
                        if(len > this.maxLen){
                            this.maxLen = len;
                        }
                    })
                })
            },
            async print() {
                let reportContent = `
					<div class="container">
						<h4 style="text-align:center">Customer overall report</h4 style="text-align:center">
						<div class="row">
							<div class="col-xs-12">
								${document.querySelector('#reportTable').innerHTML}
							</div>
						</div>
					</div>
				`;

                var mywindow = window.open('', 'PRINT', `width=${screen.width}, height=${screen.height}`);
                mywindow.document.write(`
					<?php $this->load->view('Administrator/reports/reportHeader.php'); ?>
				`);

                mywindow.document.body.innerHTML += reportContent;

                mywindow.focus();
                await new Promise(resolve => setTimeout(resolve, 1000));
                mywindow.print();
                mywindow.close();
            }
        }
    })
</script>