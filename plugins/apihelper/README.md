API Helper
===========
This plugin provides extended functionality to Blesta's current API framework.

Two key features of this currently is the ability to simply test API ability and also obtain a list of all methods available via API.

Testing API Ability
--------------------

Simply make the API call:

```
/blesta/api/Apihelper.method_list/test.json?str=hello%20world
```

If everything goes well, you should receive the same string back.

Fetching Available API Calls
-----------------------------
This is the core purpose of developing this plugin to begin with.

This call takes one argument, ```type```, and can be any of these values:

* models : Returns all available calls from those found in ```/blesta/app/models/```
* plugins : Get all calls from ```/blesta/plugins/<plugin>/models/```
* all : Returns results from both itself, ```models``` and ```plugins``` calls

If no ```type``` is passed it will return all available calls from itself

The response structure varies on the ```type``` passed.

### Models

```python
[{"Navigation":["getPrimary","getPrimaryClient","getCompany","getSystem","getSearchOptions","getPluginNav"]},{"Countries": ["getList","get","add","edit","delete"]}, ...]
```

Loops through each of the classes in ```/blesta/app/models/``` and stores them.  Calls for this look like:

```
/blesta/api/Navigation/getCompany.json
```

### Plugins

```python
{"download_manager":[{"DownloadManagerCategories":["add","edit","delete","get","getAll"]}, ...], ...}
```

This is broken down to include every plugin name, then its model and the methods callable from it.  This is because to call plugins the API call looks like this:

```
/blesta/api/plugin.model/method.fmt
```

So, for this sample, it would be

```
/blesta/api/download_manager.DownloadManagerCategories/get.json
```

This goes for all plugins.