<script>
export default {
  props: ["columns"],
  data: () => ({
    skip: 1,
    items: [],
    loading: false,
    lastDeletedItem: [],
    name: ""
  }),
  computed: {
    formURL: function() {
      return this.$route.name.replace("List", "Form");
    },
    viewURL: function() {
      return this.$route.name.replace("List", "View");
    }
  },
  methods: {
    refresh(url) {
      // console.log(url);
      var app = this;

      if (url != null) {
        //Loading indicators
        app.loading = true;
        axios
          .get(url)
          .then(({ data }) => {
            console.log(url);
            app.items = data;
            //finishes the top progress bar
          })
          .catch(function(error) {
            // this.$refs.topProgress.fail();
            app.$snack.danger({ text: error.message });
          });

        app.loading = false;
        // this.$refs.topProgress.done()
      }
    },

    onCreate() {
      //unused
    },

    onRead: async function($url) {
      return axios({
        method: "get",
        url: $url,
        responseType: "json"
      }).catch(function(error) {
        return error.response;
      });
    },

    onUpdate($url, $data) {
      return axios({
        method: "post",
        url: $url,
        responseType: "json",
        data: $data
      })
        .then(function(response) {
          return response;
        })
        .catch(function(error) {
          console.log(error.response);
          return error.response;
        });
    },

    onDelete($url, $dataId) {
      return axios({
        method: "delete",
        url: $url + "/" + $dataId,
        responseType: "json"
      })
        .then(function(response) {
          return response;
        })
        .catch(function(error) {
          console.log(error.response);
          return error.response;
        });
    },

    onDestroy(item) {
      var app = this;
      this.$swal({
        title: "Delete Record",
        text: "Sure? This action is non-reversable",
        type: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!"
      })
        .then(resp => {
          if (resp.value) {
            app
              .onDelete("/api" + app.$route.path, item.id)
              .then(function(response) {
                app.items.splice(app.items.indexOf(item), 1);
                app.$snack.success({
                  text: app.$i18n.t("general.rowDeleted"),
                  button: app.$i18n.t("general.undo"),
                  action: app.undoDeletedRow
                });
                app.lastDeletedItem = item;
              })
              .catch(function(error) {
                console.log(error.response);
                app.$snack.danger({ text: error.message });
              });
          }
        })
        .catch(ex => {
          console.log(ex.response);
          this.$toast.open({
            duration: 5000,
            message: "Error trying to delete record",
            type: "is-danger"
          });
        });
    },

    sum(details, id_prop) {
      if (details != null) {
        return details.reduce(function(sum, row) {
          return sum + new Number(row[id_prop]);
        }, 0);
      }
      return 0;
    }
  },
  beforeUpdate() {
    var app = this;

    if (app.name != app.viewURL) {
      app.$children[1].currentPage = 1;
      app.name = app.viewURL;
      app.refresh("/api" + app.$route.path);
    }
  },
  mounted() {
    var app = this;
    app.name = app.viewURL;

    app.refresh("/api" + app.$route.path);
  }
};
</script>
