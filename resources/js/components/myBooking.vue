<template>
    <div class="container">
        <h5 class="text-center">My Bookings</h5>
        <hr>
      <div class="card mb-4" v-for="(booking,new_index) in mybooking" v-bind:key="booking.id">
          <div class="card-header text-white bg-info">
              {{booking.booking.game.name}}
              <span class="float-right"><b>Playing Date </b> {{timeToDate(booking.booking.booking_date)}}</span>
          </div>
          <div class="card-body">
              <b>Booked By </b> : {{booking.booking.user.name}}
              <hr>
              <b>Slot </b> : {{booking.booking.slot.to}} - {{booking.booking.slot.from}}
              <hr>
              <Span class="text-center"><h4>Players</h4></Span>
              <div class="user-detail-col" v-for="(user,index) in booking.players" v-bind:key="user.id">
                  <span class="user-count">{{index+1}}) </span> <span><img class="rounded" v-bind:src="user.social_image" height="30px" width="30px"/> <h6>{{user.name}}</h6></span> <br>
                 
              </div>
          </div>
          <div class="card-footer">
              <div class="float-right">
                  <span  v-show="booking.booking.booking_date>current_date"><b>Note:</b> (Delete functionality On Testing Phase) </span>
                  <button v-show="booking.booking.booking_date>current_date" @click="delete_booking(booking.booking_id,new_index)" class="btn btn-danger"> Delete </button>
              </div>
          </div>
      </div>
     </div> 
</template>

<script>
    export default {
        name:"mybooking",
        data:function() {
            return {
                mybooking:[],
                current_date:parseInt(new Date().getTime()/1000)+3600,
            }
            
        },
         mounted: function(){ 
            this.axios.get('api/v1/bookings/'+this.$userId, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization-key': this.$auth_key
                    },
                })
                .then((response) => {
                    this.mybooking= response.data.body;
                })
                .catch((error) => {
                    console.error(error);
            })
         },methods:{
             timeToDate:function(date){
                 return new Date(date*1000)
             }, delete_booking : function(id,index){
                 swal({
                    title: "Are you sure want to delete ?",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                .then((willDelete) => {
                    if (willDelete) {
                        this.axios.delete('api/v1/delete_booking/'+id, {
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization-key': this.$auth_key
                            },
                        })
                        .then((response) => {
                            this.mybooking.splice(index,1);
                             swal("Information", response.data.message, "success");
                             return false;
                        })
                        .catch((error) => {
                            swal("Error", error.response.data.error_message, "error");
                        })
                    } 
                });
             }
         }
    }
</script>
<style>
  .user-detail-col {
    display: flex;
    width: 100%;
    flex-flow: row;
    margin: 0 0 20px;
  }
  .user-detail-col img {
      margin: 0 15px;
  }
  .user-detail-col span {
      display: flex;
  }
</style>