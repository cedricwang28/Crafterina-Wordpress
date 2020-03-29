

window.onload=function(){

    let flag = 0;

    document.querySelector('.fa-bars').addEventListener('click',function(){
        flag==0?flag=1:flag=0;
        if(flag){
            document.querySelector('#primary-menu').style.display = 'none';
        }else{
            document.querySelector('#primary-menu').style.display = 'block';
        }
        
    });
}