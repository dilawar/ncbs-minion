from pyramid.view import view_config
from pyramid.httpexceptions import HTTPFound
from pyramid.security import (remember, forget,)
from pyramid.view import (view_config, view_defaults, forbidden_view_config) 

from .security import groupfinder
from .authenticate import authenticate
from .resources import assertAuthentication
from . import _globals


@view_config(route_name='home', renderer='templates/home.jinja2')
def my_view(request):
    if _globals.get( "AUTHENTICATED" ):
        return HTTPFound( location = "user" )
    else:
        return HTTPFound( location = "/login" )

    return {'project': 'Hippo'}

@view_config(route_name='events', renderer='templates/events.jinja2')
def events(request):
    print( 'rendering events' )
    return { }

@view_config(route_name='AWSs', renderer='templates/aws.jinja2')
def AWSs(request):
    return { 'project' : 'Hippo' }


# login logout view.
@view_config(route_name='login', renderer='templates/login.jinja2')
def login(request):
    login_url = request.route_url('login')
    referrer = request.url
    if referrer == login_url:
        referrer = '/'  # never use login form itself as came_from
    came_from = request.params.get('came_from', referrer)
    message, login, password = '', '', ''

    if _globals.get( 'AUTHENTICATED' ):
        logging.info( "User is already authenticated" )
        return dict( message = "Already authenticated"
                , url = request.application_url + '/user' 
                , came_from = came_from 
                , login = login
                , password = password 
                , AUTHENTICATED = True
                )

    if 'form.submitted' in request.params:
        login = request.params['login']
        password = request.params['password']
        if authenticate( login, password ):
            headers = remember(request, login)
            return HTTPFound(location='/user', headers=headers)
        else:
            message = 'Failed login'
            return HTTPFound( location='/login', headers=headers )

    return dict(
        name='Login',
        message=message,
        url=request.application_url + '/login',
        came_from=came_from,
        login=login,
        password=password,
    )

@view_config(route_name='logout')
def logout(request):
    headers = forget(request)
    url = request.route_url('login')
    _globals.set( "AUTHENTICATED", False )
    _globals.set( "user", "UNKNOWN" )
    return HTTPFound(location=url, headers=headers)

# User views
@view_defaults( renderer = 'templates/user.jinja2' )
class UserView( object ):

    def __init__( self, request ):
        self.request = request
        assertAuthentication( )

    @view_config( route_name='user', renderer='templates/user.jinja2' )
    def user( self ):
        request = self.request 
        return { 'project' : 'Hippo' }

    @view_config( route_name = 'user_myprofile'
            , renderer='templates/user_myprofile.jinja2' )
    def user_myprofile( self ):
        request = self.request
        return { 'project' : 'Hippo' }

