"use strict";

var idleTime = 0;
$(document).ready(()=>{

   var idleInterval = setInterval(timerIncrement, 60000);
    $(this).mousemove((e)=>{
        idleTime = 0;
    });
    $(this).keypress((e)=>
    {
        idleTime = 0;
    });
});


function timerIncrement()
{
    try{
            idleTime = idleTime + 1;
            if(idleTime == 2)
            {
                swal("","Session will expire in less than 60 seconds!","info");
            }else if (idleTime == 3)
            { 
                
                window.location.href = baseURL+"auth/logout";
            }
            
    }catch(e){
        alert(e);
    }
}
