const server = 'http://localhost:8080/arbeitsplan/rest/';

const app = new Vue({
  el: '#vueapp',
  data : {
          showNewSchedule: false,
          newschedule: null,
          editschedule : null,
          schedules : [],
          scheduleItems : []
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

        createNewSchedule() {
            this.newschedule = {
                "idSchedule" : "-1",
                "label" : "",
                "startdate" : "2000-01-01",
                "enddate" : "2000-01-01"
            }
            this.showNewSchedule = true;
        },

      compNewScheduleOk() {
          console.log(this.newschedule.label);
          fetch(server + 'schedule/create.php', {
              body : JSON.stringify(this.newschedule), 
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

      fetchScheduleItems(idSchedule) {
            this.scheduleItems = null;

          fetch(server + 'schedule_items/read.php')
           .then( (response) => {
                if (response.status == 200 ) {
                    return response;
                } else {
                    throẃ "No data";
                }
           })
          .then( (response) => response.json() )
          .then( this.scheduleItems = response )
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
                <div><h2>Arbeitszeiten</h2>
                <button v-on:click="createNewSchedule()">Neuen Stundenplan hinzufügen</button>
                <div v-if="showNewSchedule"> 
                    <new-schedule 
                        v-on:buttonNewScheduleOk="compNewScheduleOk()" 
                        v-on:buttonNewScheduleAbort="compNewScheduleAbort()" 
                        v-bind:nschedule="newschedule"> 
                    </new-schedule>
                </div>
                <ul>
                        <li v-for="schedule in schedules">

                        <div v-if="editschedule === schedule.idSchedule">
                                Bezeichnung: <input v-model="schedule.label" v-on:keyup.13="updateSchedule(schedule)"> 
                                Startdatum: <input v-model="schedule.startdate" v-on:keyup.13="updateSchedule(schedule)"> 
                                Enddatum <input v-model="schedule.enddate" v-on:keyup.13="updateSchedule(schedule)"> 
                                <schedule-items-list> </schedule-items-list>
                                <button v-on:click="updateSchedule(schedule)" v-on:submit.prevent>Speichern</button> 
                        </div>

                        <div v-else>
                                <button v-on:click="editschedule = schedule.idSchedule" v-on:submit.prevent>BEARBEITEN</button>
                                {{schedule.label}} ({{schedule.startdate}} - {{schedule.enddate}})
                        </div>
                        </li>


                </ul>
                </div>
                `

});

Vue.component('new-schedule', {
    props : ['nschedule'],

    template: `
        <div class="new-schedule">
            Bezeichnung: <input v-model="nschedule.label" type="text"/> <br>
            Startdatum: <input v-model="nschedule.startdate" type="text" /> <br>
            Enddatum: <input v-model="nschedule.enddate" type="text" /> <br>
            <button v-on:click="$emit('buttonNewScheduleOk')">Hinzufügen</button> 
            <button v-on:click="$emit('buttonNewScheduleAbort')">Abbrechen</button>
        </div>
        `
});

Vue.component('schedule-items-list', {
        props : [ ['schedule-items'] ],
        data : function() {
                return { 
                        count : 0
                }
        },

        template : `
                <div>Component foo</div>
                `
});

Vue.component('schedule-list', {
    props : [ ['schedules'], showNewSchedule, newschedule, editschedule ],
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
                        v-on:buttonNewScheduleOk="$emit('buttonCompNewScheduleOk')" 
                        v-on:buttonNewScheduleAbort="$emit('buttonCompNewScheduleAbort()" 
                        v-bind:nschedule="newschedule"> 
                    </new-schedule>
                </div>
                <ul>
                        <li v-for="schedule in schedules">

                        <div v-if="editschedule === schedule.idSchedule">
                                Bezeichnung: <input v-model="schedule.label" v-on:keyup.13="updateSchedule(schedule)"> 
                                Startdatum: <input v-model="schedule.startdate" v-on:keyup.13="updateSchedule(schedule)"> 
                                Enddatum <input v-model="schedule.enddate" v-on:keyup.13="updateSchedule(schedule)"> 
                                <schedule-items-list> </schedule-items-list>
                                <button v-on:click="updateSchedule(schedule)" v-on:submit.prevent>Speichern</button> 
                        </div>

                        <div v-else>
                                <button v-on:click="editschedule = schedule.idSchedule" v-on:submit.prevent>BEARBEITEN</button>
                                {{schedule.label}} ({{schedule.startdate}} - {{schedule.enddate}})
                        </div>
                        </li>


                </ul>
        </div>
