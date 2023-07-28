
$(function() {
    if($("#cep").length) {
      $("#cep").mask('00000-000', {reverse: true});
    }
    // Choose filters
    jQuery(document).off('change', '.tipoBusca');
    jQuery(document).on('change', '.tipoBusca', function() {
      let _this = $(this);
      
      if(_this.val() == '1') {
        $("#buscaLinks").addClass('d-none')
        $("#buscaCategorias").removeClass('d-none')
        
        $(".linksInputs").remove();
        addNewItemSet();
      }else {
        $("#buscaCategorias").addClass('d-none')
        $("#buscaLinks").removeClass('d-none')
        
        $("#buscaCategorias input").prop("checked", false);
      }
    });
    
    
    //Links inputs
    jQuery(document).off('click', '.addNewLink');
    jQuery(document).on('click', '.addNewLink', function() {
      jQuery(this).remove();
      addNewItemSet();
    });
    
    
    jQuery(document).on('submit', '#formularioBuscaRapida', function(event) {
      var progressBar = $('#barra-progresso');
      progressBar.css("width", "100%");
    });
    
    //
    // Pipelining function for DataTables. To be used to the `ajax` option of DataTables
    //
    DataTable.pipeline = function (opts) {
      // Configuration options
      var conf = Object.assign(
          {
              pages: 5, // number of pages to cache
              url: '', // script url
              data: null, // function or object with parameters to send to the server
              // matching how `ajax.data` works in DataTables
              method: 'GET' // Ajax HTTP method
          },
          opts
      );
  
      // Private variables for storing the cache
      var cacheLower = -1;
      var cacheUpper = null;
      var cacheLastRequest = null;
      var cacheLastJson = null;
  
      return async function (request, drawCallback, settings) {
          var ajax = false;
          var requestStart = request.start;
          var drawStart = request.start;
          var requestLength = request.length;
          var requestEnd = requestStart + requestLength;
  
          if (settings.clearCache) {
              // API requested that the cache be cleared
              ajax = true;
              settings.clearCache = false;
          }
          else if (
              cacheLower < 0 ||
              requestStart < cacheLower ||
              requestEnd > cacheUpper
          ) {
              // outside cached data - need to make a request
              ajax = true;
          }
          else if (
              JSON.stringify(request.order) !==
                  JSON.stringify(cacheLastRequest.order) ||
              JSON.stringify(request.columns) !==
                  JSON.stringify(cacheLastRequest.columns) ||
              JSON.stringify(request.search) !==
                  JSON.stringify(cacheLastRequest.search)
          ) {
              // properties changed (ordering, columns, searching)
              ajax = true;
          }
  
          // Store the request for checking next time around
          cacheLastRequest = JSON.parse(JSON.stringify(request));
  
          if (ajax) {
              // Need data from the server
              if (requestStart < cacheLower) {
                  requestStart = requestStart - requestLength * (conf.pages - 1);
  
                  if (requestStart < 0) {
                      requestStart = 0;
                  }
              }
  
              cacheLower = requestStart;
              cacheUpper = requestStart + requestLength * conf.pages;
  
              request.start = requestStart;
              request.length = requestLength * conf.pages;
  
              // Provide the same `data` options as DataTables.
              if (typeof conf.data === 'function') {
                  // As a function it is executed with the data object as an arg
                  // for manipulation. If an object is returned, it is used as the
                  // data object to submit
                  var d = conf.data(request);
                  if (d) {
                      Object.assign(request, d);
                  }
              }
              else if (conf.data) {
                  // As an object, the data given extends the default
                  Object.assign(request, conf.data);
              }
  
              // Use `fetch` to make Ajax request
              let response = await fetch(
                  conf.url + '?json=' + JSON.stringify(request),
                  {
                      method: conf.method
                  }
              );
  
              let json = await response.json();
  
              cacheLastJson = JSON.parse(JSON.stringify(json));
  
              if (cacheLower != drawStart) {
                  json.data.splice(0, drawStart - cacheLower);
              }
              if (requestLength >= -1) {
                  json.data.splice(requestLength, json.data.length);
              }
  
              drawCallback(json);
          }
          else {
              json = JSON.parse(JSON.stringify(cacheLastJson));
              json.draw = request.draw; // Update the echo for each response
              json.data.splice(0, requestStart - cacheLower);
              json.data.splice(requestLength, json.data.length);
  
              drawCallback(json);
          }
      };
    };
  
    // Register an API method that will empty the pipelined data, forcing an Ajax
    // fetch on the next draw (i.e. `table.clearPipeline().draw()`)
    DataTable.Api.register('clearPipeline()', function () {
      return this.iterator('table', function (settings) {
          settings.clearCache = true;
      });
    });
  
    //
    // DataTables initialisation
    //
    $('#example').DataTable({
      ajax: DataTable.pipeline({
          url: 'scripts/server_processing.php',
          pages: 5 // number of pages to cache
      }),
      processing: true,
      serverSide: true
    });
  
    
    
  });
  
  function addNewItemSet() {
    let id = Math.random();
    let box = `<div class="input-group mt-3 linksInputs">
    <span class="input-group-text" id="link${id}">https://www.superpaguemenos.com.br/</span>
    <input type="text" class="form-control" name="links[]" placeholder="URL" id="basic-url${id}" aria-describedby="link${id}" />
    <button type="button" class="btn btn-success addNewLink"><i class="bx bx-plus"></i></button>
    </div>`;
    
    $(".container-links").append(box)
  }
  
  