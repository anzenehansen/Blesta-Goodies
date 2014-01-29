#!/usr/bin/env python2.7

import types

"""
Python-converted version of Blesta's API.

Original: https://github.com/phillipsdata/blesta_sdk/tree/master/api

Acts the same as the original.

## TODO:
#* Fix API plugin to return plugins properly:

{"plugin_name" : [{"method_file_1" : [func1, func2, ...], ...}, ...]}

Right now it returns:

'apihelper': [{'MethodList': ['fetch', 'test']}]

Should return:

'Apihelper': [{'method_list': ['fetch', 'test]}]

'Apihelper' - Get the classname and get every part before last "_"
'method_list' - Get the filename of the model to parse
"""

from json import loads as load
import httplib, urllib

"""
This is what the end-user gets as a return.

Access .response to get the response, .errors for any error messages.
"""
class BlestaResponse(object):
    
    def __init__(self, resp, code):
        self.raw = resp
        self.code = code
        # print ">> Code: %s" % self.code
        # print ">> Raw: %s" % self.raw
    
    @property
    def response(self):
        resp = self.format
        
        try:
            return resp['response']
        except:
            return None
    
    @property
    def respcode(self):
        return self.code
    
    @property
    def data(self):
        return self.raw
    
    @property
    def format(self):
        return load(str(self.raw))
    
    @property
    def errors(self):
        if self.code != 200:
            resp = self.format
            
            try:
                return resp['message']
            except KeyError:
                pass
        
        return None

class Boiler(object):
    def __init__(self, cls, model, action):
        self.model = model
        self.action = action
        self.bapi = cls
        self.__name__ = "%s.%s" % (model, action)
    
    def __call__(self, **kwargs):
        action = kwargs.pop('http_action') if "http_action" in kwargs else "GET"
        return self.bapi.get(self.model, self.action).response
    
    def __repr__(self):
        return self.__name__

class PBoiler(object):
    def __init__(self, cls, model, action):
        self.model = model
        self.action = action
        self.bapi = cls
        self.__name__ = "%s.%s" % (model, action)
    
    def __call__(self, **kwargs):
        action = kwargs.pop('http_action') if "http_action" in kwargs else "GET"
        return self.bapi.get(self.model, self.action, kwargs).response
    
    def __repr__(self):
        return self.__name__
    
class Model(object):

    def __init__(self, bapi, model, actions):
        self.__class__.__name__ = "Model.%s" % (str(model))
        self.bapi = bapi
        
        for action in actions:
            setattr(self, action, Boiler(bapi, model, action))

class Plugin(object):

    def __init__(self, bapi, model, actions):
        self.__class__.__name__ = "Plugin.%s" % (str(model))
        self.bapi = bapi
        
        for action in actions:
            setattr(self, action, PBoiler(bapi, model, action))
            
class Blank(object):
    pass

"""
Call this to get a response.  BlestaAPI(url, username, API key)

Currently only works by calling .get()/post()/put()/delete() methods.
"""
class BlestaAPI(object):
    
    def __init__(self, url, user, pasw, initial_load=True):
        self.port,self.url = url.split("://")
        self.user = user
        self.pasw = pasw
        self.host, self.uri = self.url.split("/", 1)
        
        if initial_load:
            self.methods = []
            self.models = Blank()
            self.plugins = Blank()
            
            api_calls = self.get("Apihelper.method_list", "fetch", {"type" : "all"}).response
            models = api_calls['models']
            plugins = api_calls['plugins']
            
            for model in models:
                try:
                    for cls,methods in model.items():
                        for method in methods:
                            self.methods.append("%s.%s" % (cls, method))
                        
                        setattr(self.models.__class__, cls, Model(self, cls, methods))
                except AttributeError:
                    pass
            
            for plugin, attrs in plugins.items():
                for attr in attrs:
                    for model, methods in attr.items():
                        for method in methods:
                            self.methods.append("%s.%s.%s" % (plugin, model, method))
                    
                        setattr(self.plugins.__class__, plugin, Plugin(self, "%s.%s" % (plugin, model), methods))

    def get(self, model, method, args={}, fmt="json"):
        self.model = model
        self.method = method
        self.fmt = fmt
        self.args = args
        self.action = "GET"
        
        return self.req()
    
    def post(self, model, method, args={}, fmt="json"):
        self.model = model
        self.method = method
        self.fmt = fmt
        self.args = args
        self.action = "POST"
        
        return self.req()
    
    def put(self, model, method, args={}, fmt="json"):
        self.model = model
        self.method = method
        self.fmt = fmt
        self.args = args
        self.action = "PUT"
        
        return self.req()
    
    def delete(self, model, method, args={}, fmt="json"):
        self.model = model
        self.method = method
        self.fmt = fmt
        self.args = args
        self.action = "DELETE"
        
        return self.req()
    
    def req(self):
        from base64 import encodestring as es
        
        url = "/%s%s/%s.%s" % (self.uri, self.model, self.method, self.fmt)
        self.last_req = {'url' : url, 'args' : self.args}
        
        if self.action == "GET" and len(self.args) > 0:
            url = "%s?%s" % (url, urllib.urlencode(self.args))
            self.args = None
        
        if self.args is not None:
            self.args = urllib.urlencode(self.args)
        
        if self.port == "http":
            ws = httplib.HTTPConnection(self.host, timeout=120)
        elif self.port == "https":
            ws = httplib.HTTPSConnection(self.host, timeout=120)
        else:
            raise Exception("Protocol '%s' unknown or unsupported." % (port))
        
        auth = es('%s:%s' % (self.user, self.pasw)).replace('\n', '')
        
        headers = {"Authorization" : "Basic %s" % auth}
        
	try:
            ws.request(self.action, url, self.args, headers)
	except:
	    raise Exception("Unable to establish a connection (Action: %s, URL: %s, Args: %s, Headers: %s, URI: %s, Model: %s, Method: %s)" % (self.action, url, self.args, headers, self.uri, self.model, self.method))
        
        resp = ws.getresponse()
        
        data = resp.read()
        ws.close()
        
        return BlestaResponse(data, resp.status)
    
    @property
    def last_request(self):
        return self.last_req
