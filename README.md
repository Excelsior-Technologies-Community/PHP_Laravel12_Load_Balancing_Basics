# PHP_Laravel12_Load_Balancing_Basics

# Step 1: Introduction For How To Work Load balancing in laravel:

Load balancers are used to distributes web traffics amongst two or more servers and are often used for websites which receive high volumes or traffics.

# Step 2: Install Laravel 12 Create Project 
```php
composer  create –project laravel/laravel  your folder name
```

# Step 3 : Load Balancer Methods:
Laravel Forge allows you to select one of three load balancer methods:

# 1 Round-robin:
The default method, where requests are distributed evenly across all servers.
# 2 Least connections:
Requests are sent to the server with the least connections.
# 3 IP hash:
The server to which a request is sent is determined by the client IP address. This means that requests from the same address are always handled by the same server unless it is unavailable.

# Step 4 : Now Create How To Work Load Balancing In Laravel Site
When your Laravel  project gets high traffic, you don’t want all requests to hit one server.
So you add a Load Balancer in front → it distributes traffic across multiple Laravel servers.

# Step 5 : Adding Route For Web.php file
```php
Route::get('/server-check', function () {
    return "You are on PORT: " . request()->getPort();
});
```
# What This Route Does
```php
request()->getPort() → detects which port the current request reached your server on.
```
You are on PORT: 8000
or
You are on PORT: 9000

or whatever your server is running.

# Why This Is Useful?
When you have multiple server behind a load balancer,each laravel server is run a different server port or machine

Now Your server 1 8000 then your port is 8000
Now Your server 2 8001 then your port is 8001


# Server 1 :

 <img width="1554" height="175" alt="image" src="https://github.com/user-attachments/assets/9cde5af5-3ade-44ff-971f-81ecb66e3669" />


# Server 2:
  <img width="1553" height="196" alt="image" src="https://github.com/user-attachments/assets/ace761e7-9e7f-42d6-8e7b-c97aa0031b85" />




