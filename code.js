//SESSION STORAGE

//syntax
//sessionStorage.setItem("key", "value")

//save an item
sessionStorage.setItem('name', 'simon');
//save multiple items in an object
sessionStorage.setItem('data', JSON.stringify({
    "name": "Simon",
    "stack": "MERN"
}))

//how many items are saved
sessionStorage.length //2

//get one item
sessionStorage.getItem('name') //simon

//retrieve the name of the first saved item
sessionStorage.key(0) //name
//retrieve the name of the second saved item
sessionStorage.key(1) //data

//get multiple items saved as object
JSON.parse( sessionStorage.getItem('data') ) 
//or
JSON.parse( sessionStorage.data )
//{name : "Simon", "stack" : "MERN"}

//delete an item
sessionStorage.removeItem('name')
//delete everything
sessionStorage.clear()

//Simon Ugorji 🚀 (Octagon)

// How to create a polling site using php and

//project inspired by linkedin vote feature using react

//select number of polls then find percentage of each

//%  = 100% / Number of polls

//If user chooses one, check the total votes of each poll 

// 100 then percent wil be 100 / 170 * 100%

// 50 then percent wil be 50 / 170 * 100%

// 20 then percent wil be 20 / 170 * 100%

// function to add new poll

// check if ip has voted before, then store vote or remove vote. 