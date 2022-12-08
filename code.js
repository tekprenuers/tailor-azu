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