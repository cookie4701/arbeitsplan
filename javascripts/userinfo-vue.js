const server = document.location.protocol + 'rest/'; // + '://' + document.location.host; // 'http://localhost:8080/rest/';

Vue.component('userinfo', {
        props : [ 
                'userinfo'
        ],

        template : `
                <div class="userinfo">

                </div>
        `
});

Vue.component('workareas', {
        props : [
                'workareas'
        ],

        template : `
                <div class="workareas">

                </div>
                `
});

Vue.component('schedule-item-list', {
        props : [ 'scheduleitems' ],
        data : function() {
                return { 
                        count : 0
                }
        },

        template : `
                <div class="schedule-item-list">
                <h3>Stundenplan</h3>
                        <ul>
                                <li v-for="itm in scheduleitems">{{itm.idScheduleItem}} </li>
                        </ul>
                </div>
                `
});

Vue.component('schedule-item-add', {
    props : [ 'scheduleItem' ],
    template: '#schedule-item-add'
});

Vue.component('new-schedule', {
    props : ['nschedule'],

    template: `
        <div class="new-schedule">
            Bezeichnung: <input v-model="nschedule.label" type="text"/> <br>
            Startdatum: <input v-model="nschedule.startdate" type="text" /> <br>
            Enddatum: <input v-model="nschedule.enddate" type="text" /> <br>
            <button v-on:click="$emit('buttonNewScheduleOk', nschedule )">Hinzufügen</button> 
            <button v-on:click="$emit('buttonNewScheduleAbort')">Abbrechen</button>
        </div>
        `
});

Vue.component('schedule-list', {
    props : [ 
            'schedules', 
            'showNewSchedule', 
            'newschedule', 
            'editschedule' 
    ],

    data : function() {
        return {
            count : 0
        }
    },

    template : `
        <div class="schedule-list">

                <h2>Arbeitszeiten</h2>
                <button v-on:click="$emit('buttonCreateNewSchedule') ">Neuen Stundenplan hinzufügen</button>
                <div v-if="showNewSchedule"> 
                    <new-schedule 
                        v-on:buttonNewScheduleOk="$emit('buttonNewScheduleOk', newschedule)" 
                        v-on:buttonNewScheduleAbort="$emit('buttonNewScheduleAbort')" 
                        v-bind:nschedule="newschedule"> 
                    </new-schedule>
                </div>
                <ul>
                        <li v-for="schedule in schedules">

                        <div v-if="editschedule === schedule.idSchedule">
                                Bezeichnung: <input v-model="schedule.label" v-on:keyup.13="$emit('buttonUpdateSchedule', $event, schedule)"> 
                                Startdatum: <input v-model="schedule.startdate" v-on:keyup.13="$emit('buttonUpdateSchedule', $event, schedule)"> 
                                Enddatum <input v-model="schedule.enddate" v-on:keyup.13="$emit('buttonUpdateSchedule', $event, schedule)"> 
                                <button v-on:click="$emit('buttonUpdateSchedule', schedule)" v-on:submit.prevent>Speichern</button> 
                        </div>

                        <div v-else>
                                <button v-on:click="$emit('buttonEditSchedule' , schedule.idSchedule)" v-on:submit.prevent>BEARBEITEN</button>
                                {{schedule.label}} ({{schedule.startdate}} - {{schedule.enddate}})
                        </div>
                        </li>


                </ul>
        </div>
        `
});

const app = new Vue({
  el: '#vueapp',
  data : {
          showNewSchedule: false,
          newschedule: null,
          editschedule : null,
          schedules : [],
          scheduleItems : [],
          scheduleItemAdd : { workday : 0, from : '00:00', to : '00:00' }
  },

  methods: {
        updateSchedule(schedule) {
                fetch(server + 'schedule/update.php', {body: JSON.stringify(schedule), method : "PUT", headers : { "Content-Type" : "application/json" }})
                        .then( () => {
                                this.editschedule = null;
                        })
                        .then( () => {
                                console.log('dataset updated');
                        });
        },

        compUpdateSchedule(schedule) {
                this.updateSchedule(schedule);
                this.editschedule = null;
        },

        createNewSchedule() {
            this.newschedule = {
                "idSchedule" : "-1",
                "label" : "",
                "startdate" : "2000-01-01",
                "enddate" : "2000-01-01"
            }
            this.showNewSchedule = true;
        },

      compNewScheduleOk(data) {
          fetch(server + 'schedule/create.php', {
              body : JSON.stringify(data), 
              method : "POST",
              headers : { "Content-Type" : "application/json" }
          })
            .then( () => {
                this.showNewSchedule = false;
                this.newschedule = null;
                this.fetchSchedules();
            });
      },

      compNewScheduleAbort() {
          this.showNewSchedule = false;
          this.newschedule = null;
      },

      compEditSchedule(scheduleid) {
              this.editschedule = scheduleid;
              this.fetchScheduleItems(scheduleid);
      },

      fetchScheduleItems(idSchedule) {
        this.scheduleItems = null;
        var param = { "idSchedule" : idSchedule };
        fetch(server + 'schedule_items/read.php', { 
                body : JSON.stringify(param), 
                method : "POST",
                headers : {
                        "Content-Type" : "application/json"
                }
        })

              .then( (response) => {
                      if (response.status == 200 ) {
                              return response;
                      } else {
                              throw "HTTP status error";
                      }
              })

              .then( (response) => {
                      return response.json();
              })

              .then( (data) => {
                      this.scheduleItems = data;
              })

              .catch( (err) => {
                      console.log(err);
              });
      },

      fetchSchedules() {

          this.schedules = null;

          fetch(server + 'schedule/read.php')
                .then( (response) => {
                    if (response.status == 200 ) {
                        return response;
                    } else {
                        throw "No data";
                    }
                })
                .then(response => response.json() )
                .then( (data) => {
                        this.schedules = data;
                })
                .catch( (data) => {
                    console.log(data);
                })
      },

      fetchScheduleItemsOld(idSchedule) {
          this.scheduleItems = null;

          fetch(server + 'schedule_items/read.php')
           .then( (response) => {
                if (response.status == 200 ) {
                    return response;
                } else {
                    throw 'No data';
                }
           })
          .then( (response) => response.json() )

          .then( (response) => {
                  this.scheduleItems = response
          })

          .catch( (data) => {
              console.log(data);
          });

    }
  },

  mounted() {
      this.fetchSchedules();
      this.scheduleItems = null;
  },

        template: `
                <div class="app">
                <schedule-list
                        v-bind:newschedule="newschedule" 
                        v-bind:showNewSchedule="showNewSchedule"
                        v-bind:schedules="schedules"
                        v-bind:editschedule="editschedule"
                        v-on:buttonCreateNewSchedule="createNewSchedule"
                        v-on:buttonNewScheduleOk="compNewScheduleOk"
                        v-on:buttonNewScheduleAbort="compNewScheduleAbort"
                        v-on:buttonUpdateSchedule="compUpdateSchedule"
                        v-on:buttonEditSchedule="compEditSchedule"
                >
                </schedule-list>

                <div v-if="editschedule">
                        <schedule-item-list
                                :scheduleitems="scheduleItems" >

                        </schedule-item-list>

                        <schedule-item-add v-bind:scheduleItem="scheduleItemAdd">

                        </schedule-item-add>
                </div>
                </div>
                `

});

