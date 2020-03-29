

window.onload=function(){

    let flag = 0;

    document.querySelector('.fa-bars').addEventListener('click',function(){
        
        if(flag){
            document.querySelector('#primary-menu').style.display = 'none';
            flag==0?flag=1:flag=0;
        }else{
            document.querySelector('#primary-menu').style.display = 'block';
            flag==0?flag=1:flag=0;
        }
        
    });
}