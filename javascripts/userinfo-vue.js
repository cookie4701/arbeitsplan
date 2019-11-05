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

        methods: {
                        getStringDayOfWeek(nbr) {
                                if (nbr === 0) {
                                        return "Montag";
                                } else if (nbr === 1) {
                                        return "Dienstag";
                                } else if ( nbr === 2 ) {
                                        return "Mittwoch";
                                } else if ( nbr === 3 ) {
                                        return "Donnerstag";
                                } else if ( nbr === 4 ) {
                                        return "Freitag";
                                } else if ( nbr === 5 ) {
                                        return "Samstag";
                                } else if ( nbr === 6 ) {
                                        return "Sonntag";
                                } else {
                                        return "...";
                                }
                        }
        },

        template : `
                <div class="schedule-item-list">
                <h3>Stundenplan</h3>
                <table>
                        <tr>
                                <td> </td>
                                <td>Tag</td>
                                <td>Von</td>
                                <td>Bis</td>
                        </tr>
                        <tr v-for="itm in scheduleitems">
                                <td><button v-on:click="$emit('buttonRemoveScheduleItem', itm.idScheduleItem);">X</button></td>
                                <td>{{getStringDayOfWeek(itm.dayOfWeek)}}</td>
                                <td>{{itm.time_from}}</td>
                                <td>{{itm.time_to}}</td>
                        </tr>
                </table>
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
          scheduleItemAdd : null 
  },

  methods: {
        resetScheduleItemAdd() {
                if ( this.editschedule === null) {
                        this.scheduleItemAdd = { idSchedule: 0, workday: 0, from : '00:00', to : '00:00' };
                } else {
                        this.scheduleItemAdd = { idSchedule : this.editschedule, workday : 0, from: '00:00', to : '00:00' };
                }
        },

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
              this.resetScheduleItemAdd();
      },

      createScheduleItem(scheduleitem) {
        fetch(server + 'schedule_items/create.php', {
                body : JSON.stringify(scheduleitem),
                method : "POST",
                headers : {
                        "Content-Type" : "application/json"
                }
        })
              .then( (response) => {
                      if ( response === "not ok" ) throw "Unable to save new entry";

                      return response;
              })
              .then( (response) => {
                      console.log('dataset created');
                      if ( this.editschedule !== null ) {
                              return response;
                      } else {
                              console.log('idSchedule is null');
                      }
              })


              .catch( (err) => {
                      console.log('error!');
              });
              
              this.fetchScheduleItems(this.editschedule);

      },

      fetchScheduleItems(idSchedule) {
        this.scheduleItems = null;
        var param = { "idSchedule" : idSchedule };
        console.log('fetch with: ' + JSON.stringify(param) );

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
                      this.resetScheduleItemAdd();
                      return true;
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
                        return true;
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

    },
        compScheduleItemListRemoveElement(idScheduleItem) {
                var param = { "idScheduleItem" : idScheduleItem };
                fetch(server + 'schedule_items/delete.php', {
                        body : JSON.stringify(param),
                        method : "POST",
                        headers : {
                                "Content-Type" : "application/json"
                        }
                })
                        .then ( (response => response.text() ) )
                        .then( (response) => {
                                if ( response === 'ok' ) {
                                        return response;
                                } else {
                                        throw 'Unable to delete';
                                }
                        })

                        .then( () => {
                                this.fetchScheduleItems(this.editschedule);
                                return true;
                        })

                        .catch( (err) => {
                                console.log(err);
                        });
                        
                       
        }
  },

  mounted() {
      this.fetchSchedules();
      this.scheduleItems = null;
      this.resetScheduleItemAdd();
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
                                :scheduleitems="scheduleItems" 
                                v-on:buttonRemoveScheduleItem="compScheduleItemListRemoveElement"
                        >

                        </schedule-item-list>

                        <schedule-item-add 
                                v-bind:schedule-item="scheduleItemAdd"
                                v-on:buttonAddScheduleItem="createScheduleItem"
                        >

                        </schedule-item-add>
                </div>
                </div>
                `

});

/*
 *
 *

                                */
