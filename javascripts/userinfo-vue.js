const server = 'http://localhost:8080/rest/';

const app = new Vue({
  el: '#vueapp',
  data : {
          editschedule : null,
          schedules : []
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
        }
  },

  mounted() {
          fetch(server + 'schedule/read.php')
                .then(response => response.json() )
                .then( (data) => {
                        this.schedules = data;
                })
  },

        template: `
                <div><h2>Arbeitszeiten</h2>
                <ul>
                        <li v-for="schedule in schedules">

                        <div v-if="editschedule === schedule.idSchedule">
                                Bezeichnung: <input v-model="schedule.label" v-on:keyup.13="updateSchedule(schedule)"> 
                                Startdatum: <input v-model="schedule.startdate" v-on:keyup.13="updateSchedule(schedule)"> 
                                Enddatum <input v-model="schedule.enddate" v-on:keyup.13="updateSchedule(schedule)"> 
                                <schedule_items_list> </schedule_items_list>
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

Vue.component('schedule_items_list', {
        data : function() {
                return { 
                        count : 0
                }
        },

        template : `
                <div>Component foo</div>
                `
});
